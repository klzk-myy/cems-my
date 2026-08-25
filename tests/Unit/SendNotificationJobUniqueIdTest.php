<?php

namespace Tests\Unit;

use App\Jobs\SendNotificationJob;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class SendNotificationJobUniqueIdTest extends TestCase
{
    private function notifiable(int $id): object
    {
        return (object) ['id' => $id];
    }

    private function notification(): Notification
    {
        return new class extends Notification {};
    }

    public function test_same_collection_members_produce_same_id_regardless_of_order(): void
    {
        $notification = $this->notification();

        $job1 = new SendNotificationJob(collect([$this->notifiable(1), $this->notifiable(2)]), $notification);
        $job2 = new SendNotificationJob(collect([$this->notifiable(2), $this->notifiable(1)]), $notification);

        $this->assertSame($job1->uniqueId(), $job2->uniqueId());
    }

    public function test_different_collections_produce_different_ids(): void
    {
        $notification = $this->notification();

        $job1 = new SendNotificationJob(collect([$this->notifiable(1)]), $notification);
        $job2 = new SendNotificationJob(collect([$this->notifiable(2)]), $notification);

        $this->assertNotSame($job1->uniqueId(), $job2->uniqueId());
    }

    public function test_single_notifiable_keeps_stable_id(): void
    {
        $notification = $this->notification();

        $job1 = new SendNotificationJob($this->notifiable(7), $notification);
        $job2 = new SendNotificationJob($this->notifiable(7), $notification);

        $this->assertSame($job1->uniqueId(), $job2->uniqueId());
        $this->assertNotSame($job1->uniqueId(), (new SendNotificationJob($this->notifiable(8), $notification))->uniqueId());
    }
}
