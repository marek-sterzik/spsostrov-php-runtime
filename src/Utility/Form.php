<?php

namespace App\Utility;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Exception\ResponseException;
use Symfony\Component\Form\FormInterface as FormComponent;

class Form
{
    private string $template = "form.html.twig";
    private array $templateVars = [];
    private ?string $caption = null;

    /** @var callable */
    private $renderer;
    private array $actions = [];

    public function __construct(private FormComponent $form, private Request $request, callable $renderer)
    {
        $this->renderer = $renderer;
    }

    public function useTemplate(string $template, array $templateVars = []): self
    {
        $this->template = $template;
        $this->templateVars = $templateVars;
        return $this;
    }

    public function caption(?string $caption): self
    {
        $this->caption = $caption;
        return $this;
    }

    public function handle(): Response
    {
        foreach ($this->actions as $action) {
            $baseClass = "btn";
            if (!$action['validated']) {
                $baseClass .= " non-validated-action";
            }
            $options = array_merge([
                "attr" => ["class" => $baseClass . " " . $action['type']],
                "row_attr" => ["class" => "md-3 me-3 d-inline-block"],
            ], $action['options'], [
                "label" => $action['label'],
            ]);
            $this->form->add($action['id'], SubmitType::class, $options);
        }
        
        $this->form->handleRequest($this->request);
        if (!$this->form->isSubmitted()) {
            return $this->renderForm();
        }
        $clickedAction = null;
        foreach ($this->actions as $action) {
            /** @phpstan-ignore-next-line */
            if ($this->form->get($action['id'])->isClicked()) {
                $clickedAction = $action;
                break;
            }
        }
        if ($clickedAction === null || ($clickedAction['validated'] && !$this->form->isValid())) {
            return $this->renderForm();
        }

        $handler = $clickedAction['handler'];

        return $handler($this->form->getData(), $this->form) ?? $this->renderForm();
    }

    public function action(
        string $label,
        callable $handler,
        bool $validated = true,
        array $options = [],
        string $type = 'btn-primary',
    ): self {
        $actionIndex = count($this->actions);
        $this->actions[] = [
            "id" => "_action_" . $actionIndex,
            "label" => $label,
            "handler" => $handler,
            "validated" => $validated,
            "options" => $options,
            "type" => $type,
        ];
        return $this;
    }

    public function getData(): mixed
    {
        return $this->form->getData();
    }

    public function getForm(): FormComponent
    {
        return $this->form;
    }

    private function renderForm(): Response
    {
        $renderer = $this->renderer;
        return $renderer($this->template, array_merge($this->templateVars, [
            "form" => $this->form->createView(),
            "caption" => $this->caption,
        ]));
    }
}
