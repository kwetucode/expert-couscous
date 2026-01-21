<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;
use Livewire\Component;

class ContactForm extends Component
{
    public bool $showModal = false;

    public string $name = '';
    public string $email = '';
    public string $subject = '';
    public string $messageContent = '';

    public bool $submitted = false;
    public bool $isLoading = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'min:5', 'max:200'],
            'messageContent' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 100 caractères.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'Veuillez fournir une adresse e-mail valide.',
            'subject.required' => 'Le sujet est obligatoire.',
            'subject.min' => 'Le sujet doit contenir au moins 5 caractères.',
            'subject.max' => 'Le sujet ne doit pas dépasser 200 caractères.',
            'messageContent.required' => 'Le message est obligatoire.',
            'messageContent.min' => 'Le message doit contenir au moins 10 caractères.',
            'messageContent.max' => 'Le message ne doit pas dépasser 2000 caractères.',
        ];
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    #[On('openContactModal')]
    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function submit(): void
    {
        $this->validate();

        $this->isLoading = true;

        try {
            // Données du contact
            $contactData = [
                'name' => $this->name,
                'email' => $this->email,
                'subject' => $this->subject,
                'messageContent' => $this->messageContent,
            ];

            // Option 1: Envoyer par email (si configuré)
            if (config('mail.mailers.smtp.host')) {
                Mail::send('emails.contact', $contactData, function ($mail) use ($contactData) {
                    $mail->to(config('mail.from.address', 'contact@easyvente.com'))
                        ->replyTo($contactData['email'], $contactData['name'])
                        ->subject('Contact: ' . $contactData['subject']);
                });
            }

            // Logger pour le développement
            logger()->info('Nouveau message de contact', $contactData);

            $this->submitted = true;
            $this->isLoading = false;

            // Dispatch event pour notification
            $this->dispatch('contact-submitted');

        } catch (\Exception $e) {
            $this->isLoading = false;
            logger()->error('Erreur envoi contact: ' . $e->getMessage());
            session()->flash('error', 'Une erreur est survenue. Veuillez réessayer.');
        }
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->subject = '';
        $this->messageContent = '';
        $this->submitted = false;
        $this->isLoading = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
