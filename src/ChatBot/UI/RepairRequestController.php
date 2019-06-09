<?php


namespace App\ChatBot\UI;


use App\ChatBot\Application\RepairRequestAcceptanceService;
use App\ChatBot\Infrastructure\QuestionsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

class RepairRequestController extends AbstractController
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

    public function handshake(Request $request): Response
    {
        $session = new Session(new NativeSessionStorage(), new AttributeBag());
        $session->start();
        $jsonResponse = new JsonResponse([], Response::HTTP_OK);
        $jsonResponse->setData(['token' => $session->getId()]);

        return $jsonResponse;
    }

    public function openSession(Request $request)
    {
        if (!$request->headers->has('X-Chat-Token')) {
            throw new \RuntimeException();
        }

    }


    public function replyToMessage(Request $request): Response
    {
        $allSession = [];
        $filename = $this->getParameter('kernel.project_dir').'/var/storage.json';
        if (file_exists($filename)) {
            $session = file_get_contents($filename);
            $allSession = json_decode($session, true);
        }
        $token = $request->headers->get('X-Chat-Token');
        $sessionData = &$allSession[$token];

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
            ];
            $result = [
                'text' => 'Привет, я бот службы ЖКХ мастер. Буду рад помочь.',
                'actions' => $greetingActions,
            ];

            return new JsonResponse($result, Response::HTTP_OK);
        }

        $actions = $this->questionsRepository->all();

        if ('Задать вопрос' === $message) {
            $result = $this->getQuestionActions($message, $actions);
        } elseif ('Отправить заявку' === $message || $sessionData['repair_request_started']) {
            $sessionData['repair_request_started'] = true;

            $response = $this->handleRepairRequest($message, $sessionData);
            file_put_contents($filename, json_encode($allSession));

            return $response;
        } else {
            $greetingActions = [
                'Задать вопрос',
                'Отправить заявку',
                'Оставить жалобу/предложение',
            ];
            $result = [
                'text' => 'Извините я Вас не понял. Могу предложить:',
                'actions' => $greetingActions,
            ];

            return new JsonResponse($result, Response::HTTP_OK);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    /**
     * @param $message
     * @param array $actions
     * @return array
     */
    public function getQuestionActions($message, array $actions): array
    {
        $isOneOfKnownQuestions = array_key_exists($message, $actions);
        if ($isOneOfKnownQuestions) {
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

        return $arr;
    }

    public function handleRepairRequest($message, &$sessionData): Response
    {
        $objectTypes = [
            'Поломка',
            'Протечка',
            'Другое',
        ];

        $emptyData = ['repair_request_started' => true] === $sessionData;
        if ($emptyData) {
            $result = [
                'text' => 'Что случилось?',
                'actions' => $objectTypes,
            ];

            $sessionData['object-type-req'] = true;

            return new JsonResponse($result, Response::HTTP_OK);
        }

        $jobTypes = [
            'Починить кран',
            'Клининговые услуги',
            'Покрасить стены',
        ];

        if (in_array($message, $objectTypes, true)) {
            $sessionData = array_merge($sessionData, ['object-type' => $message,]);

            $result = [
                'text' => "Принято, $message. Какой вид работ требуется?",
                'actions' => $jobTypes,
            ];

            return new JsonResponse($result, Response::HTTP_OK);
        }

        if (in_array($message, $jobTypes, true)) {
            $sessionData = array_merge(
                $sessionData,
                ['job-type' => $message, 'requested-phone' => true]
            );

            $result = [
                'text' => "Принято, $message. Введите пожлуйста ваш номер телеона для связи?",
                'actions' => [],
            ];

            return new JsonResponse($result, Response::HTTP_OK);
        }


        if (array_key_exists('requested-phone', $sessionData)) {
            unset($sessionData['requested-phone']);
            $sessionData = array_merge($sessionData, ['phone' => $message, 'requested-address' => true]);

            $result = [
                'text' => "Принято, $message. На какой адрес вызов?",
                'actions' => [],
            ];

            return new JsonResponse($result, Response::HTTP_OK);
        }

        if (array_key_exists('requested-address', $sessionData)) {
            unset($sessionData['requested-address']);
            $sessionData = array_merge($sessionData, ['address' => $message]);

            $taskNumber = $this->acceptanceService->acceptRepairRequest(
                $sessionData['phone'],
                $sessionData['address'],
                $sessionData['object-type'],
                $sessionData['job-type']
            );

            $result = [
                'text' => "Принято, $message. Ваша заявка зарегистрирована. Номер заявки $taskNumber.".
                    'Хотите отслеживать статус заявки, присоединяйтесь к нашему чат <a href="https://tele.click/digitalp_bot">боту</a>.',
                'actions' => [],
            ];

            $sessionData['repair_request_started'] = false;

            return new JsonResponse($result, Response::HTTP_OK);
        }

    }

}