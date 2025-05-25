<?php

namespace App\Controller;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use App\Submission\SubmissionManager;
use App\Submission\SubmissionState;
use App\Assignment\AssignmentState;
use App\Form\FileSubmitType;
use App\Entity\Assignment;
use App\Entity\Submission;
use App\Entity\User;
use App\Lock\LockManager;
use App\FileManager\FileManager;
use App\Component\Action;
use App\Utility\UploadLimit;

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
        "submission_does_not_exist" =>
            "Odevzdání už neexistuje.",
    ];

    public function __construct(
        private FileManager $fileManager,
        private SubmissionManager $submissionManager
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
        return $this->submissionManager->lockOn(
            $assignment->getSubmissionDescriptor($user),
            true,
            function ($submission) use ($assignment, $error, $errorMessage, $request) {
                if ($submission === null) {
                    return $this->redirectBack(true);
                }
                if ($submission->getState()->isLockedDraft()) {
                    return $this->redirectToRoute('submission-close', [
                        "assignment" => $assignment->getId(),
                        "_back" => false
                    ]);
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
                        "upload_limit" => UploadLimit::get(),
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
            }
        );
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{assignment}/files", name: 'submission-file-action')]
    public function fileAction(Assignment $assignment, Request $request): Response
    {
        $user = $this->getUserEntity();
        $descriptor = $assignment->getSubmissionDescriptor($user);
        $error = null;
        $deleteFile = $request->query->get("delete");
        if (is_string($deleteFile)) {
            $error = $this->submissionManager->deleteFile($descriptor, $deleteFile) ?
                null : $this->submissionManager->getLastError();
        }
        $mvFrom = $request->query->get("mvfrom");
        $mvTo = $request->query->get("mvto");
        if (is_string($mvFrom) && is_string($mvTo)) {
            $error = $this->submissionManager->moveFile($descriptor, $mvFrom, $mvTo) ?
                null : $this->submissionManager->getLastError();
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
    #[Route("/submission/{assignment}/lock", name: 'submission-lock')]
    public function lockAction(Assignment $assignment): Response
    {
        $user = $this->getUserEntity();
        if ($this->submissionManager->lockSubmission($assignment->getSubmissionDescriptor($user))) {
            return $this->redirectToRoute('submission-close', [
                "assignment" => $assignment->getId(),
                "_back" => false,
            ]);
        } else {
            return $this->redirectToRoute('create-submission', [
                "assignment" => $assignment->getId(),
                "_back" => false,
            ]);
        }
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{assignment}/close", name: 'submission-close')]
    public function closeAction(Assignment $assignment, Request $request): Response
    {
        $force = $request->query->get("force") ? true : false;
        $descriptor = $assignment->getSubmissionDescriptor($this->getUserEntity());

        $success = $this->submissionManager->closeLockedSubmission($descriptor, $force);

        if ($success) {
            return $this->redirectToRoute(
                'submission-detail',
                ["submission" => $this->submissionManager->getLastProcessedSubmission()->getId(), "_back" => false]
            );
        }

        $errorCode = $this->submissionManager->getLastError();
        
        if ($errorCode === 'force_required_override_late' || $errorCode === 'force_required_override_early') {
            return $this->confirmSubmission(
                $this->submissionManager->getLastProcessedSubmission(),
                $errorCode === 'force_required_override_late'
            );
        }

        return $this->redirectToRoute('create-submission', [
            "assignment" => $assignment->getId(),
            "_back" => false,
        ]);
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/{submission}/dismiss", name: 'submission-dismiss')]
    public function dismissAction(Submission $submission): Response
    {
        $user = $this->getUserEntity();
        if ($submission->getSubmitter() === $user && $submission->getState()->isDraft()) {
            $this->submissionManager->deleteSubmission($submission->getSubmissionDescriptor());
        }
        return $this->redirectBack(true);
    }

    private function confirmSubmission(Submission $submission, bool $overrideLateSubmitted): Response
    {
        $assignment = $submission->getAssignment();
        $confirmLink = $this->generateUrl("submission-close", [
            "assignment" => $assignment->getId(),
            "_back" => false,
            "force" => "true",
        ]);
        $dismissLink = $this->generateUrl("submission-dismiss", [
            "submission" => $submission->getId(),
            "_back" => false,
        ]);
        $confirmAction = Action::get($confirmLink)
            ->label("dokončit odevzdání")->cssClass("btn-danger")
        ;
        $dismissAction = Action::get($dismissLink)
            ->label("zrušit odevzdání")->cssClass("btn-secondary")
            ->confirm("Zrušením odevzdání bude práce smazána a zapomenuta. Chcete pokračovat?", "Potvrdit smazání")
            ->confirmButtons("zrušit odevzdání a smazat práci", "zpět")
        ;
        return $this->render("confirm-submission.html.twig", [
            "confirm" => $confirmAction,
            "dismiss" => $dismissAction,
            "overrideLateSubmitted" => $overrideLateSubmitted,
        ]);
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

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("submit");
    }
}
