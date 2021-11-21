<?php
namespace App\Services\LiveStream\Agora;
use App\Services\LiveStream\Agora\AccessToken;

class RtcTokenBuilder {
    const ROLE_ATTENDEE = 0;
    const ROLE_PUBLISHER = 1;
    const ROLE_SUBSCRIBER = 2;
    const ROLE_ADMIN = 101;

    # appID: The App ID issued to you by Agora. Apply for a new App ID from 
    #        Agora Dashboard if it is missing from your kit. See Get an App ID.
    # appCertificate:	Certificate of the application that you registered in 
    #                  the Agora Dashboard. See Get an App Certificate.
    # channelName:Unique channel name for the AgoraRTC session in the string format
    # userAccount: The user account. 
    # role: Role_Publisher = 1: A broadcaster (host) in a live-broadcast profile.
    #       Role_Subscriber = 2: (Default) A audience in a live-broadcast profile.
    # privilegeExpireTs: represented by the number of seconds elapsed since 
    #                    1/1/1970. If, for example, you want to access the
    #                    Agora Service within 10 minutes after the token is 
    #                    generated, set expireTimestamp as the current 
    public static function buildTokenWithUid($appID, $appCertificate, $channelName, $userAccount, $role, $privilegeExpireTs){
        $token = AccessToken::init($appID, $appCertificate, $channelName, $userAccount);
        $privileges = AccessToken::PRIVILEGES;
        $token->addPrivilege($privileges["kJoinChannel"], $privilegeExpireTs);
        if(($role == RtcTokenBuilder::ROLE_ATTENDEE) ||
            ($role == RtcTokenBuilder::ROLE_PUBLISHER) ||
            ($role == RtcTokenBuilder::ROLE_ADMIN))
        {
            $token->addPrivilege($privileges["kPublishVideoStream"], $privilegeExpireTs);
            $token->addPrivilege($privileges["kPublishAudioStream"], $privilegeExpireTs);
            $token->addPrivilege($privileges["kPublishDataStream"], $privilegeExpireTs);
        }
        return $token->build();
    }
}