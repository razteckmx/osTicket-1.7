<?php
/*********************************************************************
    tickets.php

    Main client/user interface.
    Note that we are using external ID. The real (local) ids are hidden from user.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('secure.inc.php');

if(!is_object($thisclient) || !$thisclient->isValid()) die(lang('access_denied')); //Double check again.
require_once(INCLUDE_DIR.'class.ticket.php');
$ticket=null;
if($_REQUEST['id']) {
    if(!($ticket=Ticket::lookupByExtId($_REQUEST['id']))) {
        $errors['err']=lang('invalid_ticket_id');
    }elseif(!$ticket->checkClientAccess($thisclient)) {
        $errors['err']=lang('invalid_ticket_id'); //Using generic message on purpose!
        $ticket=null;
    }
}

//Process post...depends on $ticket object above.
if($_POST && is_object($ticket) && $ticket->getId()):
    $errors=array();
    switch(strtolower($_POST['a'])){
    case 'reply':
        if(!$ticket->checkClientAccess($thisclient)) //double check perm again!
            $errors['err']=lang('access_denied').'. '.lang('posibly_invalid_id');

        if(!$_POST['message'])
            $errors['message']=lang('message_required');

        if(!$errors) {
            //Everything checked out...do the magic.
            $vars = array('message'=>$_POST['message']);
            if($cfg->allowOnlineAttachments() && $_FILES['attachments'])
                $vars['files'] = AttachmentFile::format($_FILES['attachments'], true);

            if(($msgid=$ticket->postMessage($vars, 'Web'))) {
                $msg=lang('message_posted');
            } else {
                $errors['err']=lang('cant_post_message');
            }

        } elseif(!$errors['err']) {
            $errors['err']=lang('errors_occurred');
        }
        break;
    default:
        $errors['err']=lang('unknown_action_only');
    }
    $ticket->reload();
endif;
$nav->setActiveNav('tickets');
if($ticket && $ticket->checkClientAccess($thisclient)) {
    $inc='view.inc.php';
} elseif($cfg->showRelatedTickets() && $thisclient->getNumTickets()) {
    $inc='tickets.inc.php';
} else {
    $nav->setActiveNav('new');
    $inc='open.inc.php';
}
include(CLIENTINC_DIR.'header.inc.php');
include(CLIENTINC_DIR.$inc);
include(CLIENTINC_DIR.'footer.inc.php');
?>
