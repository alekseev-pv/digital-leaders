<?php


namespace App\ChatBot\UI;


use App\ChatBot\Application\RepairRequestAcceptanceService;
use App\ChatBot\Infrastructure\QuestionsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

class RepairRequestController
{

    /**
     * @var RepairRequestAcceptanceService
     */
    private $acceptanceService;
    /**
     * @var DecoderInterface
     */
    private $decoder;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var QuestionsRepository
     */
    private $questionsRepository;

    public function __construct(
        RepairRequestAcceptanceService $acceptanceService,
        DecoderInterface $decoder,
        LoggerInterface $logger,
        QuestionsRepository $questionsRepository
    ) {
        $this->acceptanceService = $acceptanceService;
        $this->decoder = $decoder;
        $this->logger = $logger;
        $this->questionsRepository = $questionsRepository;
    }

    public function acceptRepairRequest(Request $request): Response
    {
        $content = $request->getContent();
        $this->logger->debug('Repair request with payload', [$content]);
        $payload = $this->decoder->decode($content, 'json');

        $phoneNumber = $payload['phone'];
        $objectType = $payload['object-type'];
        $jobType = $payload['job-type'];
        $address = $payload['address'];

        $requestId = $this->acceptanceService->acceptRepairRequest(
            $phoneNumber,
            $address,
            $objectType,
            $jobType
        );

        return new JsonResponse(['id' => $requestId], Response::HTTP_CREATED);
    }

    public function replyToMessage(Request $request): Response
    {
        // todo fix raw strings. Only for mvp version.
        $content = $request->getContent();
        $this->logger->debug('Message came to api', [$content]);
        $payload = $this->decoder->decode($content, 'json');
        $message = $payload['message'];
        $messageNumber = (int)$payload['message_number'];

        if (1 === $messageNumber) {
            $greetingActions = [
                'Задать вопрос',
                'Отправить заявку',
                'Оставить жалобу/предложение',
            ];
            $arr = [
                'text' => 'Привет, я бот службы ЖКХ мастер. Буду рад помочь.',
                'actions' => $greetingActions,
            ];

            return new JsonResponse($arr, Response::HTTP_OK);
        }

        $actions = $this->questionsRepository->all();

        $isOneOfKnownQuestions = array_key_exists($message, $actions);
        if ('Задать вопрос' === $message) {
            $arr = [
                'text' => 'Вы можете узнать ответы на вопросы:',
                'actions' => array_keys($actions),
            ];

        } elseif ($isOneOfKnownQuestions) {
            $arr = [
                'text' => $actions[$message],
                'actions' => ['Хотите задать еще вопрос?'],
            ];
        } else {
            $arr = [
                'text' => 'Вы можете узнать ответы на вопросы:',
                'actions' => array_keys($actions),
            ];
        }

        return new JsonResponse($arr, Response::HTTP_OK);
    }

}