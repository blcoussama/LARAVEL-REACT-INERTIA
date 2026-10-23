<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for a local installation: `php artisan migrate:fresh --seed`.
 *
 * Every account, name and e-mail address below is FICTITIOUS (example.com is
 * a reserved domain) and they all share the demo password "password".
 * Never run this seeder on a server reachable from the Internet.
 */
class DatabaseSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        if (User::where('email', 'admin@example.com')->exists()) {
            $this->command?->warn('Demo data already present: run "php artisan migrate:fresh --seed" to start over.');

            return;
        }

        $password = Hash::make(self::DEMO_PASSWORD);

        $users = collect([
            'admin' => ['Admin Demo', 'admin@example.com', User::ROLE_ADMIN],
            'alice' => ['Alice Martin', 'alice@example.com', User::ROLE_MEMBER],
            'karim' => ['Karim Benali', 'karim@example.com', User::ROLE_MEMBER],
            'sofia' => ['Sofia Lopez', 'sofia@example.com', User::ROLE_MEMBER],
            'yanis' => ['Yanis Moreau', 'yanis@example.com', User::ROLE_MEMBER],
        ])->map(function (array $row) use ($password) {
            [$name, $email, $role] = $row;

            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->role = $role;
            $user->email_verified_at = now();
            $user->save();

            return $user;
        });

        $team = $users->values();

        foreach ($this->projects() as $index => $definition) {
            [$name, $ownerKey, $status, $dueInDays, $description, $tasks] = $definition;
            $owner = $users[$ownerKey];

            $project = Project::create([
                'name' => $name,
                'description' => $description,
                'status' => $status,
                'due_date' => now()->addDays($dueInDays)->toDateString(),
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ]);

            foreach ($tasks as $position => $taskName) {
                $assignee = $team[($index + $position + 1) % $team->count()];

                Task::create([
                    'name' => $taskName,
                    'description' => "$taskName for the \"$name\" project.",
                    'status' => $this->taskStatus($status, $position),
                    'priority' => Task::PRIORITIES[($index + $position) % 3],
                    'due_date' => now()->addDays($dueInDays - 5 * (count($tasks) - $position))->toDateString(),
                    'project_id' => $project->id,
                    'assigned_user_id' => $assignee->id,
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ]);
            }
        }
    }

    private function taskStatus(string $projectStatus, int $position): string
    {
        return match ($projectStatus) {
            'completed' => 'completed',
            'pending' => $position === 0 ? 'in_progress' : 'pending',
            default => ['completed', 'in_progress', 'pending'][$position % 3],
        };
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: int, 4: string, 5: list<string>}>
     */
    private function projects(): array
    {
        return [
            ['Company website redesign', 'alice', 'in_progress', 45,
                'New visual identity and responsive pages for the public website.',
                ['Collect content from each department', 'Design the home page mock-up', 'Build the page templates', 'Migrate the blog articles', 'Set up redirects from old URLs']],
            ['Mobile ordering app', 'karim', 'pending', 120,
                'Prototype of a mobile app to order products and follow deliveries.',
                ['Write the user stories', 'Choose the mobile framework', 'Design the checkout flow', 'Prepare the API contract']],
            ['CRM data migration', 'admin', 'in_progress', 30,
                'Move the customer records from spreadsheets to the new CRM.',
                ['Inventory the existing spreadsheets', 'Clean duplicate contacts', 'Map the fields to the CRM', 'Import a test batch', 'Train the sales team']],
            ['Customer support portal', 'sofia', 'pending', 90,
                'Self-service portal where customers open and follow their tickets.',
                ['List the most frequent requests', 'Draft the FAQ pages', 'Define ticket categories', 'Configure e-mail notifications']],
            ['Q4 marketing campaign', 'alice', 'completed', -10,
                'Seasonal campaign on social networks and newsletter.',
                ['Define the campaign budget', 'Create the visuals', 'Schedule the posts', 'Report on the results']],
            ['Internal API documentation', 'yanis', 'in_progress', 20,
                'Document the internal REST endpoints used by the front-end teams.',
                ['List all the endpoints', 'Write request and response examples', 'Describe the error codes', 'Publish the documentation site']],
            ['Office network upgrade', 'karim', 'completed', -30,
                'Replace the Wi-Fi access points and the main switch.',
                ['Audit the current equipment', 'Order the new access points', 'Install and test the network']],
            ['Recruitment platform MVP', 'admin', 'pending', 150,
                'First version of a platform to publish job offers and receive applications.',
                ['Interview the HR team', 'Sketch the candidate journey', 'Estimate the development effort']],
            ['Inventory dashboard', 'sofia', 'in_progress', 60,
                'Dashboard showing stock levels and low-stock alerts.',
                ['Define the key indicators', 'Connect the stock database', 'Build the charts', 'Add low-stock alerts', 'Review with the warehouse team']],
            ['Accessibility audit', 'yanis', 'pending', 40,
                'Check the main applications against accessibility guidelines.',
                ['Choose the audit checklist', 'Test keyboard navigation', 'Check colour contrast', 'Write the recommendations']],
            ['Payment gateway integration', 'alice', 'in_progress', 25,
                'Accept card payments on the online shop through a payment provider.',
                ['Compare payment providers', 'Implement the sandbox integration', 'Handle payment webhooks', 'Test refunds']],
            ['Onboarding guide for new hires', 'admin', 'completed', -5,
                'Guide describing tools, accounts and first-week checklist.',
                ['Gather the existing documents', 'Write the first-week checklist', 'Review the guide with managers']],
        ];
    }
}
