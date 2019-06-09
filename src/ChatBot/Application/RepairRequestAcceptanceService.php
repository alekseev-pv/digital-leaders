<?php


namespace App\ChatBot\Application;


use App\ChatBot\Infrastructure\TaskTrackerNotificationService;

class RepairRequestAcceptanceService
{

    /**
     * @var TaskTrackerNotificationService
     */
    private $notificationService;

    public function __construct(TaskTrackerNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function acceptRepairRequest(
        string $forPhoneNumber,
        string $address,
        string $proposedObjectType,
        string $jobType
    ): string {


        return $this->notificationService->createRepairRequest($forPhoneNumber, $address, $proposedObjectType, $jobType);
    }
}