<?php

namespace App\Controller;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use App\Submission\SubmissionState;
use App\Assignment\AssignmentState;
use App\Form\FileSubmitType;
use App\Repository\SubmissionRepository;
use App\Entity\Assignment;
use App\Entity\Submission;
use App\Entity\User;
use App\Lock\LockManager;
use App\FileManager\FileManager;
use App\Job\JobManager;

class SubmissionController extends AbstractController
{
    const ERRORS = [
        "submission_is_closed" =>
            "Odevzdání už je uzavřeno a nelze jej měnit.",
        "delete_file_failed" =>
            "Nelze smazat soubor.",
        "invlid_file_name" =>
            "Neplatný název souboru.",
        "moved_file_does_not_exist" =>
            "Přejmenovávaný soubor neexistuje.",
        "destination_file_already_exist" =>
            "Cílovy soubor už existuje.",
        "move_file_failed" =>
            "Nelze přejmenovat soubor.",
        "incomplete_upload_file_limit_reached" => 
            "Některé soubory nebyly nahrány protože zadání má omezen počet souborů k odevzdání.",
        "incomplete_upload_size_limit_reached" => 
            "Některé soubory nebyly nahrány protože zadání má omezenu celkovou velikost souborů.",
    ];
    public function __construct(
        private SubmissionRepository $submissionRepository,
        private LockManager $lockManager,
        private FileManager $fileManager,
        private JobManager $jobManager,
    ) {
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{assignment}", name: 'create-submission')]
    public function index(Assignment $assignment, Request $request): Response
    {
        $error = $request->query->get('err');
        if (!is_string($error)) {
            $error = null;
        }
        $errorMessage = null;
        if ($error !== null) {
            $errorMessage = self::ERRORS[$error] ?? sprintf("Nastala neznámá chyba: %s.", $error);
        }
        $this->enableModule("upload-files");
        $user = $this->getUserEntity();
        if ($user === null) {
            return $this->redirectBack(true);
        }
        return $this->lock($assignment, $user, function () use ($assignment, $user, $error, $errorMessage, $request) {
            $submission = $this->ensureSubmissionExists($assignment, $user);
            if ($submission === null) {
                return $this->redirectBack(true);
            }

            $files = $this->fileManager->listFiles($submission);

            $fileLimit = $this->makeLimit($assignment->getFileLimit(), count($files));
            $sizeLimit = $this->makeLimit($assignment->getSizeLimit(), $this->totalSize($files));

            $caption = "Nahrát soubory";

            $templateArgs = [
                "files" => $files,
                "assignment" => $assignment,
                "submissionId" => $submission->getId(),
                "errorCode" => $error,
                "errorMessage" => $errorMessage,
            ];

            $fileLimitOk = ($fileLimit === null || $fileLimit > 0) ? true : false;
            $sizeLimitOk = ($sizeLimit === null || $sizeLimit > 0) ? true : false;

            if ($fileLimitOk && $sizeLimitOk) {
                $formOptions = [
                    "attr" => ["class" => "with-progress"],
                    "file_limit" => $fileLimit,
                    "size_limit" => $sizeLimit,
                    "upload_limit" => $this->getUploadLimit(),
                ];
                return $this->form(FileSubmitType::class, [], $formOptions)
                ->action("nahrát soubory", function (array $data) use ($submission, $fileLimit, $sizeLimit) {
                    $file = $data['file'];
                    if (!is_array($file)) {
                        $file = [$file];
                    }
                    $urlParams = [
                        "assignment" => $submission->getAssignment()->getId(),
                        "_back" => false,
                    ];
                    $err = $this->submitFiles($file, $submission, $fileLimit, $sizeLimit);
                    if ($err !== null) {
                        $urlParams['err'] = $err;
                    }
                    return $this->redirectToRoute("create-submission", $urlParams);
                })
                ->caption($caption)
                ->useTemplate("upload.html.twig", $templateArgs)
                ->handle()
                ;
            } else {
                if ($request->getMethod() === "POST") {
                    if (!$fileLimitOk) {
                        $err = 'incomplete_upload_file_limit_reached';
                    } else {
                        $err = 'incomplete_upload_size_limit_reached';
                    }
                    $urlParams = [
                        "assignment" => $assignment->getId(),
                        "_back" => false,
                        'err' => $err,
                    ];
                    return $this->redirectToRoute("create-submission", $urlParams);
                } else {
                    $templateArgs['caption'] = $caption;
                    $templateArgs['form'] = null;
                    return $this->render("upload.html.twig", $templateArgs);
                }
            }
        });
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{assignment}/files", name: 'submission-file-action')]
    public function fileAction(Assignment $assignment, Request $request): Response
    {
        $user = $this->getUserEntity();
        $submission = $this->ensureSubmissionExists($assignment, $user);
        $error = null;
        if ($submission->getId() !== null && $submission->getSubmitter() === $user) {
            $deleteFile = $request->query->get("delete");
            if (is_string($deleteFile)) {
                $error = $this->fileManager->deleteFile($submission, $deleteFile);
            }
            $mvFrom = $request->query->get("mvfrom");
            $mvTo = $request->query->get("mvto");
            if (is_string($mvFrom) && is_string($mvTo)) {
                $error = $this->fileManager->moveFile($submission, $mvFrom, $mvTo);
            }
        }
        $routeParams = [
            "assignment" => $assignment->getId(),
            "_back" => false,
        ];
        if ($error) {
            $routeParams['err'] = $error;
        }
        return $this->redirectToRoute('create-submission', $routeParams);
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{assignment}/close", name: 'submission-close')]
    public function closeAction(Assignment $assignment): Response
    {
        $user = $this->getUserEntity();
        return $this->lock($assignment, $user, function () use ($assignment, $user) {
            $submission = $this->ensureSubmissionExists($assignment, $user);
            if ($submission->getId() === null || $submission->getSubmitter() !== $user) {
                return $this->redirectToRoute('create-submission', [
                    "assignment" => $assignment->getId(),
                    "_back" => false,
                ]);
            }
            $submission->setSubmittedAt(new DateTimeImmutable());
            $submission->setState(SubmissionState::Submitted);
            $this->fileManager->putManifest($submission);
            $this->getEntityManager()->flush();
            if ($assignment->getSubmissionMode()->deleteOld()) {
                $submissions = [];
                foreach ($this->submissionRepository->selectCurrentFor($submission) as $deactivatedSubmission) {
                    $deactivatedSubmission->setCurrent(false);
                    $submissions[] = $deactivatedSubmission->getId();
                }
                if (!empty($submissions)) {
                    $this->getEntityManager()->flush();
                    foreach ($submissions as $submissionId) {
                        $this->jobManager->invoke("remove_submission", ["id" => $submissionId, "force" => true]);
                    }
                }
            } else {
                $this->submissionRepository->updateCurrentFor($submission);
            }
            $this->jobManager->invoke("close_submission", ["id" => $submission->getId()]);
            return $this->redirectToRoute(
                'submission-detail',
                ["submission" => $submission->getId(), "_back" => false]
            );
        });
    }

    private function makeLimit(?int $totalLimit, int $usedLimit): ?int
    {
        if ($totalLimit === null) {
            return null;
        }
        $totalLimit -= $usedLimit;
        $totalLimit = max($totalLimit, 0);
        return $totalLimit;
    }

    private function totalSize(array $files): int
    {
        $size = 0;
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $size += $file->getSize() ?: 0;
            } else {
                $size += $file->getByteCount() ?? 0;
            }
        }
        return $size;
    }

    private function submitFiles(array $files, Submission $submission, ?int $fileLimit, ?int $sizeLimit): ?string
    {
        if ($submission->getId() === null) {
            $this->getEntityManager()->flush();
        }
        $ret = null;
        if ($fileLimit !== null && count($files) > $fileLimit) {
            $ret = "incomplete_upload_file_limit_reached";
            $files = array_slice($files, 0, $fileLimit);
        }

        if ($sizeLimit !== null && $this->totalSize($files) > $sizeLimit) {
            $ret = "incomplete_upload_size_limit_reached";
            $oldFiles = $files;
            $files = [];
            foreach ($oldFiles as $file) {
                $fileSize = $file->getSize() ?: 0;
                if ($sizeLimit > $fileSize) {
                    $sizeLimit -= $fileSize;
                    $files[] = $file;
                } else {
                    break;
                }
            }
        }
        
        $this->fileManager->addFiles($submission, $files);
        return $ret;
    }

    private function ensureSubmissionExists(Assignment $assignment, User $user): ?Submission
    {
        if (!$assignment->canSubmit($user)) {
            return null;
        }
        $submission = $this->submissionRepository->getLastSubmission($assignment, $user);
        if ($submission !== null && $submission->getState() === SubmissionState::Draft) {
            return $submission;
        }

        if ($submission === null || $assignment->getSubmissionMode()->allowMultiple()) {
            $submission = new Submission($assignment, $user);
            $this->getEntityManager()->persist($submission);
            return $submission;
        }

        return null;
    }

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("submit");
    }

    private function lock(Assignment $assignment, User $user, callable $criticalSection): mixed
    {
        $lock = sprintf("sc-%d-%d", $assignment->getId(), $user->getId());
        $this->lockManager->lock($lock);
        try {
            return $criticalSection();
        } finally {
            $this->lockManager->unlock($lock);
        }
    }

    private function getUploadLimit(): ?int
    {
        $limit1 = $this->limitToBytes(ini_get('upload_max_filesize'));
        $limit2 = $this->limitToBytes(ini_get('post_max_size'));
        return min($limit1, $limit2);
    }

    private function limitToBytes($val): int
    {
        $val  = trim($val);

        if (is_numeric($val))
            return $val;

        $last = strtolower($val[strlen($val)-1]);
        $val  = (int)substr($val, 0, -1);

        switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
        }

        return $val;
    }
}
