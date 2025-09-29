<?php 

namespace App\Traits;

use App\Mail\RegistrationDetails;
use Illuminate\Support\Facades\Mail;

trait SendMailTrait
{
    public function sendEmailToCustomerAndAdmins($object,$class)
    {
        // Email to Client
        Mail::to($object->client->email)
            ->send(new $class($object, true));

        // Email to agent and admins
        Mail::to($object->createdBy->email)
            ->cc(config('constants.emails'))
            ->send(mailable: new $class($object));
        
    }
    public function sendRegistrationDetailsToClient($client)
    {
        $mail = Mail::to($client->email);
        $mail->send(new RegistrationDetails($client));

    }
}