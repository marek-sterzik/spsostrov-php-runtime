<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Sync\Sync;

class SyncInfoController extends AbstractController
{
    const FIELD_HEADING = [
        "name" => "název",
        "driver" => "ovladač",
        "enabled" => "synchronizace zapnuta",
        "forceEnabled" => "vždy zapnuto",
        "test" => "test",
        "remoteHost" => "vzdálený stroj",
        "remotePath" => "vzdálená cesta",
        "username" => "uživatel",
        "password" => "heslo",
        "sshKey" => "ssh klíč",
    ];

    const FIELD_CLASSES = [
        "driver" => "font-monospace",
        "remoteHost" => "font-monospace",
        "remotePath" => "font-monospace",
        "password" => "font-monospace",
        "sshKey" => "font-monospace",
    ];

    const FIELD_CONVERTORS = [
        "test" => "testConvertor",
    ];

    public function __construct(private Sync $sync)
    {
    }

    #[IsGranted('ROLE_SUPERADMIN')]
    #[Route("/sync-info", name: "sync-info")]
    public function index(): Response
    {
        $this->enableModule("sync-info");
        $config = $this->sync->getConfig();
        $config['test'] = $this->sync->isEnabled();

        $configHtml = [];
        foreach (self::FIELD_HEADING as $field => $heading) {
            if (array_key_exists($field, $config)) {
                $value = $config[$field];
                $value = $this->convertValue($value, $field);
                if ($value !== null) {
                    $configHtml[] = [
                        "heading" => preg_replace('/\s+/', '&nbsp;', $heading),
                        "valueHtml" => $value,
                        "class" => self::FIELD_CLASSES[$field] ?? null,
                    ];
                }
                unset($config[$field]);
            }
        }
        foreach ($config as $field => $value) {
            $value = $this->convertValue($value, $field);
            if ($value !== null) {
                $configHtml[] = [
                    "heading" => htmlspecialchars($field),
                    "valueHtml" => $value,
                    "class" => self::FIELD_CLASSES[$field] ?? null,
                ];
            }
        }

        return $this->render("sync-info.html.twig", ["config" => $configHtml]);
    }

    #[IsGranted('ROLE_SUPERADMIN')]
    #[Route("/test-sync", name: "test-sync")]
    public function doTest(): Response
    {
        $result = $this->sync->testConnection();
        return $this->json(["testResult" => $result]);
    }

    private function convertValue(mixed $value, string $field): ?string
    {
        $convertor = self::FIELD_CONVERTORS[$field] ?? 'defaultConvertor';
        return $this->$convertor($value);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function defaultConvertor(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '<span class="badge bg-success">ano</span>' : '<span class="badge bg-danger">ne</span>';
        }
        if (is_string($value)) {
            return htmlspecialchars($value);
        }
        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function testConvertor(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }
        $spinner = "<div class=\"spinner-grow spinner-grow-sm text-secondary me-2\" role=\"status\"></div>";
        $loadingMessage = "<span>načítá se...</span>";
        $widget = sprintf(
            "<div class=\"sync-test-status\" data-test-url=\"%s\">%s%s</div>",
            htmlspecialchars($this->generateUrl("test-sync")),
            $spinner,
            $loadingMessage
        );
        return $widget;
    }
}
