<?php
declare(strict_types=1);

namespace App\Subscriber;

use App\Exception\Api\AbstractApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if (!$e instanceof AbstractApiException) {
            return;
        }

        $this->logger->warning('API error', [
            'type' => $e->getType(),
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
            'query' => $request->query->all(),
        ]);

        $event->setResponse(new JsonResponse([
            'type' => $e->getType(),
            'title' => $this->translator->trans('api_error.title'),
            'status' => $e->getStatus(),
            'detail' => $e->getMessage(),
            'context' => $e->getContext(),
        ], $e->getStatus()));
    }
}
