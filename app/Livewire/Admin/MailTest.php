<?php

namespace App\Livewire\Admin;

use App\Mail\PinResetIssuedEmail;
use App\Mail\WelcomeEmail;
use App\Services\AuditService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Mail test')]
class MailTest extends Component
{
    public string $to = '';

    public string $subject = '';

    public string $body = '';

    public string $template = 'plain';

    public string $deliveryMode = 'sync';

    public ?string $resultType = null;   // 'success' | 'error'

    public ?string $resultMessage = null;

    public ?string $resultDetails = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', \App\Models\User::class);

        $this->to = auth()->user()->email ?? '';
        $this->subject = 'Test email from '.config('app.name');
        $this->body = "Hi,\n\nThis is a test email from ".config('app.name').
            " to verify the SMTP configuration is working.\n\nSent: ".now()->toDateTimeString().' UTC';
    }

    public function send(AuditService $audit): void
    {
        $this->validate([
            'to' => 'required|email|max:255',
            'subject' => 'required|string|max:200',
            'body' => 'required|string|max:5000',
            'template' => 'required|in:plain,welcome,pin_reset',
            'deliveryMode' => 'required|in:sync,queue',
        ]);

        $this->resultType = null;
        $this->resultMessage = null;
        $this->resultDetails = null;

        // Synthetic recipient for previewing branded templates
        $previewUser = new \App\Models\User([
            'name' => 'Test Recipient',
            'username' => 'preview',
            'email' => $this->to,
        ]);
        $previewUser->id = 0;

        try {
            if ($this->template === 'welcome') {
                $mailable = new WelcomeEmail($previewUser, '123456');
            } elseif ($this->template === 'pin_reset') {
                $mailable = new PinResetIssuedEmail($previewUser, '123456');
            } else {
                $mailable = null;
            }

            if ($mailable) {
                $this->deliveryMode === 'queue'
                    ? Mail::to($this->to)->queue($mailable)
                    : Mail::to($this->to)->send($mailable);
            } else {
                // Plain text test — sync only, since Mail::raw doesn't queue cleanly
                $subject = $this->subject;
                $body = $this->body;

                Mail::raw($body, function ($m) use ($subject) {
                    $m->to($this->to)->subject($subject);
                });
            }

            $this->resultType = 'success';
            $this->resultMessage = $this->deliveryMode === 'queue'
                ? 'Queued. Email will be sent on the next queue tick (cron, ~60s).'
                : 'Sent successfully — check the inbox for '.$this->to.'.';

            $audit->log('mail.test_sent', null, [
                'to' => $this->to,
                'template' => $this->template,
                'mode' => $this->deliveryMode,
            ]);
        } catch (\Throwable $e) {
            $this->resultType = 'error';
            $this->resultMessage = 'Failed: '.$e->getMessage();
            $this->resultDetails = (string) $e;

            $audit->log('mail.test_failed', null, [
                'to' => $this->to,
                'template' => $this->template,
                'error' => substr($e->getMessage(), 0, 200),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.mail-test');
    }
}
