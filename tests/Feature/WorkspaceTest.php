<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_workspace_data(): void
    {
        $user = User::factory()->create(['name' => 'Nguyễn Lan']);
        $user->todos()->create(['title' => 'Gửi báo giá', 'due_on' => today()]);
        $user->recurringTasks()->create(['title' => 'Gửi phiếu thu', 'frequency' => 'monthly', 'day_of_month' => 5, 'starts_on' => today()->subMonth()]);
        User::factory()->create()->notices()->create(['title' => 'Họp giao ban']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Home')
                ->has('notices', 1)
                ->has('todos', 1)
                ->has('recurring.week', 7)
                ->has('recurring.upcoming', 1)
                ->has('truongVinh.children', 5));
    }

    public function test_anyone_can_post_a_notice_but_only_the_author_can_delete_it(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($author)->post(route('notices.store'), ['title' => 'Mẫu hợp đồng mới'])->assertRedirect();
        $notice = Notice::query()->sole();

        $this->actingAs($other)->delete(route('notices.destroy', $notice))->assertForbidden();
        $this->actingAs($author)->delete(route('notices.destroy', $notice))->assertRedirect();
        $this->assertSoftDeleted($notice);
    }

    public function test_todos_are_private(): void
    {
        $owner = User::factory()->create();
        $todo = $owner->todos()->create(['title' => 'Việc riêng']);

        $this->actingAs(User::factory()->create())
            ->patch(route('todos.update', $todo), ['done' => true])
            ->assertForbidden();

        $this->actingAs($owner)->patch(route('todos.update', $todo), ['done' => true])->assertRedirect();
        $this->assertNotNull(Todo::query()->find($todo->id)->done_at);
    }

    public function test_recurring_task_validation_and_completion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('recurring-tasks.store'), ['title' => 'Gửi phiếu thu', 'frequency' => 'monthly'])
            ->assertSessionHasErrors('day_of_month');

        $this->actingAs($user)
            ->post(route('recurring-tasks.store'), ['title' => 'Gửi phiếu thu', 'frequency' => 'monthly', 'day_of_month' => 5])
            ->assertRedirect();

        $task = $user->recurringTasks()->sole();
        $this->actingAs($user)->post(route('recurring-tasks.complete', $task), ['due_on' => '2026-11-05'])->assertRedirect();
        $this->assertSame(1, $task->completions()->count());
    }
}
