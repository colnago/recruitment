<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isJsonApiRequest($request)) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $response = new JsonResponse([
                'error' => [
                    'message' => $exception->getMessage(),
                ],
            ], $exception->getStatusCode());
        } else {
            $response = new JsonResponse([
                'error' => [
                    'message' => 'Internal error',
                ],
            ], 500);
        }

        $event->setResponse($response);
    }

    private function isJsonApiRequest(Request $request): bool
    {
        $contentType = (string) $request->headers->get('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        $accept = (string) $request->headers->get('Accept');
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        return false;
    }
}