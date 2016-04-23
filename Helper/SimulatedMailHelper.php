<?php

/* 
 * @copyright   2016 Linh Nguyen (vln104) at mk8r.com. All rights reserved.
 * @author      Linh Nguyen
 */

namespace MauticPlugin\VLNSpamassassincheckerBundle\Helper;

use Mautic\EmailBundle\Helper\MailHelper as BaseMailHelper;

class SimulatedMailHelper extends BaseMailHelper
{
    private $emailAddress;
    private $name;
    
    public function simulateSendMailContent()
    {
        $from = $this->message->getFrom();
        if (empty($from)) {
            $this->setFrom($this->from);
        }

        // Set system return path if applicable
        $this->message->setReturnPath($this->generateBounceEmail($this->idHash));
        
        //$this->addUnsubscribeHeader(); - method is private so code copied below
        if (isset($this->idHash)) {
            $unsubscribeLink = $this->factory->getRouter()->generate('mautic_email_unsubscribe', array('idHash' => $this->idHash), true);
            $this->headers['List-Unsubscribe'] = "<$unsubscribeLink>";
        }
        
        
        $this->message->setSubject($this->subject);
        $this->message->setBody($this->body['content'], $this->body['contentType'], $this->body['charset']);
        
        //this was not present in 1.2.2 but present in 1.2.3 code
        if (function_exists(setMessagePlainText))
        {
            $this->setMessagePlainText();
        }
        
        if (method_exists($this->message, 'addMetadata')) {
            $this->message->addMetadata($this->emailAddress,
                array(
                    'leadId'   => (!empty($this->lead)) ? $this->lead['id'] : null,
                    'emailId'  => (!empty($this->email)) ? $this->email->getId() : null,
                    'hashId'   => $this->idHash,
                    'source'   => $this->source,
                    'tokens'   => $this->getTokens()
                )
            );
        } 
        else 
        {
            // Replace token content
            $tokens = $this->getTokens();
            if (!empty($tokens)) {
                // Replace tokens
                $search  = array_keys($tokens);
                $replace = $tokens;

                BaseMailHelper::searchReplaceTokens($search, $replace, $this->message);
            }
        }
        
        // Attach assets
        if (!empty($this->assets)) {
            /** @var \Mautic\AssetBundle\Entity\Asset $asset */
            foreach ($this->assets as $asset) {
                if (!in_array($asset->getId(), $this->attachedAssets)) {
                    $this->attachedAssets[] = $asset->getId();
                    $this->attachFile(
                        $asset->getFilePath(),
                        $asset->getOriginalFileName(),
                        $asset->getMime()
                    );
                }
            }
        }

        // Set custom headers
        if (!empty($this->headers)) {
            $headers = $this->message->getHeaders();
            foreach ($this->headers as $headerKey => $headerValue) {
                if ($headers->has($headerKey)) {
                    $header = $headers->get($headerKey);
                    $header->setFieldBodyModel($headerValue);
                } else {
                    $headers->addTextHeader($headerKey, $headerValue);
                }
            }
        }

        return $this->message->toString();
    }
    
    public function setTo($emailaddress, $name)
    {
        parent::setTo($emailaddress, $name);
        
        $this->emailAddress = $emailaddress;
        $this->name = $name;
    }
}