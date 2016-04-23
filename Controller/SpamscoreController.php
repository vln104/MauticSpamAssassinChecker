<?php

/* 
 * @copyright   2016 Linh Nguyen (vln104) at mk8r.com. All rights reserved.
 * @author      Linh Nguyen
 */

namespace MauticPlugin\VLNSpamassassincheckerBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\VLNSpamassassincheckerBundle\SpamChecker\PostmarkSpamChecker;
use MauticPlugin\VLNSpamassassincheckerBundle\Helper\SimulatedMailHelper;

class SpamscoreController extends CommonController
{
    public function spamscoreAction($emailId)
    {
        $emailModel    = $this->factory->getModel('email');
        
        $user  = $this->factory->getUser();
        $email = $emailModel->getEntity($emailId);
        $emailContent = $this->getEmailContent($emailId);
        
        file_put_contents(__DIR__ . '/email.txt', $emailContent);
        
        $spamScoreChecker = new PostmarkSpamChecker();
        
        $spamScoreChecker->calculateSpamScore($emailContent, $email, $user);
        $success = $spamScoreChecker->getSuccessState();
        
        if ($success)
        {
            $spamDetail = $spamScoreChecker->getDetailInfo();
            $summaryScore = $spamScoreChecker->getSummaryScore();
        
            return $this->delegateView(
                    array('viewParameters' => array(
                        'spamDetail' => $spamDetail,
                        'summaryScore' => $summaryScore
                    ),
                    'contentTemplate' => 'VLNSpamassassincheckerBundle:Email:spamscore.html.php'
                )
            );
        }
        else
        {
            return $this->delegateView(
                    array('viewParameters' => array(
                        'spamDetail' => '',
                        'summaryScore' => 'Unable to retrieve spam score from postmark API'
                    ),
                    'contentTemplate' => 'VLNSpamassassincheckerBundle:Email:spamscore.html.php'
                )
            ); 
        }
    }
    
    private function getEmailContent($objectId)
    {
        $factory = $this->factory;
    	$model  = $factory->getModel('email');
        $entity = $model->getEntity($objectId);

        //simulate a real email as best as possible by assigning headers

        //make a new ID - this enables Mautic to put in the unsubscribe headers
        $idHash = uniqid();
        
        $mailHelper = new SimulatedMailHelper($factory, \Swift_Mailer::newInstance(\Swift_SmtpTransport::newInstance('0.0.0.0', 0)), array(
                    $factory->getParameter('mailer_from_email') => $factory->getParameter('mailer_from_name')));
        $mailHelper->setEmail($entity);
        $mailHelper->setLead($lead);
        $mailHelper->setIdHash($idHash);
        $mailHelper->setTo('myemail@spamtest.com', 'Spam Tester');
        
        return $mailHelper->simulateSendMailContent();
    }
}

