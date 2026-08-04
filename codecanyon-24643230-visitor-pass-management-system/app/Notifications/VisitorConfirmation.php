<?php

namespace App\Notifications;

use App\Enums\VisitorStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;
use Illuminate\Notifications\Messages\MailMessage;

class VisitorConfirmation extends Notification
{
    use Queueable;

    private $visitingDetails;

    /**
     * SendInvitationToVisitors constructor.
     * @param $visitingDetails
     */
    public function __construct($visitingDetails)
    {
        $this->visitingDetails = $visitingDetails;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $array = ['database'];
        if (
            setting('twilio_disabled') != 0 &&
            !blank(setting('twilio_from')) &&
            !blank(setting('twilio_auth_token')) &&
            !blank(setting('twilio_account_sid'))
        ) {
            array_push($array, TwilioChannel::class);
        }

        if (
            setting('notifications_email') != false &&
            !blank(setting('mail_host')) &&
            !blank(setting('mail_username')) &&
            !blank(setting('mail_password')) &&
            !blank(setting('mail_port')) &&
            !blank(setting('mail_from_name')) &&
            !blank(setting('mail_from_address'))
        ) {
            array_push($array, 'mail');
        }

        return $array;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $visitor = array(
            'name'   => $this->visitingDetails->visitor->name,
            'status' => $this->visitingDetails->status,
            'phone' => $this->visitingDetails->visitor->phone,
        );

        return (new MailMessage)
            ->subject("Visiting Confirmation#".$this->visitingDetails->reg_no)
            ->markdown('admin.mail.visitor.mail', ['visitor'=>$visitor]);
    }

    /**
     * @param $notifiable
     * @return \NotificationChannels\Twilio\TwilioMessage|TwilioSmsMessage
     */
    public function toTwilio($notifiable)
    {
        if($this->visitingDetails->status == VisitorStatus::ACCEPT){
            $message = 'Dear '.$this->visitingDetails->visitor->name.', has accepted your appointment request. Thank you';
        }elseif($this->visitingDetails->status == VisitorStatus::REJECT){
            $message = 'Dear '.$this->visitingDetails->visitor->name.', Staff is not available to meet you at the moment.Visit request declined by '.$this->visitingDetails->employee->name.' Reschedule your appointment later.Thank You';
        }

        return (new TwilioSmsMessage())
            ->content($message);
    }
    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
