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
    ];

    public function __construct(private Sync $sync)
    {
    }

    #[IsGranted('ROLE_SUPERADMIN')]
    #[Route("/sync-info", name: "sync-info")]
    public function index(): Response
    {
        $config = $this->sync->getConfig();

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

    private function convertValue(mixed $value, string $field): ?string
    {
        /** @phpstan-ignore-next-line */
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
}
