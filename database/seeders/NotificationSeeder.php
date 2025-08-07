<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users, or create 10 if none exist.
        $users = User::all();
        if ($users->isEmpty()) {
            $users = User::factory()->count(10)->create();
        }

        // Create 50 notifications
        Notification::factory()->count(50)->create()->each(function (Notification $notification) use ($users) {

            // For each notification, attach it to a random number of users (from 1 to all users).
            $randomUsers = $users->random(rand(1, $users->count()));

            // Attach the users to the notification via the pivot table.
            $notification->users()->attach($randomUsers);

            // Now, for some of these attachments, let's mark them as "read".
            foreach ($randomUsers as $user) {
                // We'll randomly decide if the notification should be marked as read for this user.
                // This will create a mix of read and unread notifications.
                $isRead = fake()->boolean(40); // 40% chance of being read

                if ($isRead) {
                    // Update the pivot table record to set the 'read_at' timestamp.
                    $notification->users()->updateExistingPivot($user->id, [
                        'read_at' => now()->subDays(rand(0, 30)), // Mark as read at a random time in the last month
                    ]);
                }
            }
        });
    }
}
