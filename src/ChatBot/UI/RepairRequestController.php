<?php


namespace App\ChatBot\UI;


use App\ChatBot\Application\RepairRequestAcceptanceService;
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

    public function __construct(
        RepairRequestAcceptanceService $acceptanceService,
        DecoderInterface $decoder,
        LoggerInterface $logger
    ) {
        $this->acceptanceService = $acceptanceService;
        $this->decoder = $decoder;
        $this->logger = $logger;
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

}