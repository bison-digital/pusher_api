<?php

namespace Drupal\pusher_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\pusher_api\DTO\Data;
use Drupal\pusher_api\Event\AuthenticationEvent;
use Drupal\pusher_api\Service\PusherService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuthenticationController extends ControllerBase {

  public function __construct(
    protected EventDispatcherInterface $eventDispatcher,
    protected PusherService $pusherService,
  ) {
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('pusher_api.pusher.service.default'),
    );
  }

  public function login(Request $request): JsonResponse {
    if ($this->currentUser()->isAuthenticated() == FALSE) {
      return new JsonResponse('Forbidden', 403);
    }

    if (empty($request->request->get('socket_id'))) {
      return new JsonResponse(['error' => 'Socket ID not found.'], 400);
    }

    $data = new Data(['id' => (string) $this->currentUser->id()]);

    $event = $this->eventDispatcher->dispatch(new AuthenticationEvent($data));

    $data = $event->getData();

    return $this->pusherService->authenticateUser(
      $request->request->get('socket_id'),
      $data,
    );
  }
}