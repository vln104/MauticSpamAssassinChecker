<?php

/* 
 * @copyright   2016 Linh Nguyen (vln104) at mk8r.com and Mautic project. All rights reserved.
 * @author      Linh Nguyen
 */

namespace MauticPlugin\VLNSpamassassincheckerBundle\Controller;

use Mautic\EmailBundle\Controller\EmailController as BaseEmailController;

/*
 * This class extends the Email Controller in Mautic to insert a tab for the Spam Score
 */
class EmailController extends BaseEmailController
{
    /*
     * This overrides the viewAction in Mautic's default email controller.
     * This function has the same functionality but adds a tab at the bottom to display SpamAssassin info
     */
    public function viewAction($objectId)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model    = $this->factory->getModel('email');
        $security = $this->factory->getSecurity();

        /** @var \Mautic\EmailBundle\Entity\Email $email */
        $email = $model->getEntity($objectId);
        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.email.page', 1);

        if ($email === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_email_index', array('page' => $page));

            return $this->postActionRedirect(
                array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => 'MauticEmailBundle:Email:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_email_index',
                        'mauticContent' => 'email'
                    ),
                    'flashes'         => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.email.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                )
            );
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'email:emails:viewown',
            'email:emails:viewother',
            $email->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        //get A/B test information
        list($parent, $children) = $model->getVariants($email);
        $properties   = array();
        $variantError = false;
        $weight       = 0;
        if (count($children)) {
            foreach ($children as $c) {
                $variantSettings = $c->getVariantSettings();

                if (is_array($variantSettings) && isset($variantSettings['winnerCriteria'])) {
                    if ($c->isPublished()) {
                        if (!isset($lastCriteria)) {
                            $lastCriteria = $variantSettings['winnerCriteria'];
                        }

                        //make sure all the variants are configured with the same criteria
                        if ($lastCriteria != $variantSettings['winnerCriteria']) {
                            $variantError = true;
                        }

                        $weight += $variantSettings['weight'];
                    }
                } else {
                    $variantSettings['winnerCriteria'] = '';
                    $variantSettings['weight']         = 0;
                }

                $properties[$c->getId()] = $variantSettings;
            }

            $properties[$parent->getId()]['weight']         = 100 - $weight;
            $properties[$parent->getId()]['winnerCriteria'] = '';
        }

        $abTestResults = array();
        $criteria      = $model->getBuilderComponents($email, 'abTestWinnerCriteria');
        if (!empty($lastCriteria) && empty($variantError)) {
            if (isset($criteria['criteria'][$lastCriteria])) {
                $testSettings = $criteria['criteria'][$lastCriteria];

                $args = array(
                    'factory'    => $this->factory,
                    'email'      => $email,
                    'parent'     => $parent,
                    'children'   => $children,
                    'properties' => $properties
                );

                //execute the callback
                if (is_callable($testSettings['callback'])) {
                    if (is_array($testSettings['callback'])) {
                        $reflection = new \ReflectionMethod($testSettings['callback'][0], $testSettings['callback'][1]);
                    } elseif (strpos($testSettings['callback'], '::') !== false) {
                        $parts      = explode('::', $testSettings['callback']);
                        $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                    } else {
                        $reflection = new \ReflectionMethod(null, $testSettings['callback']);
                    }

                    $pass = array();
                    foreach ($reflection->getParameters() as $param) {
                        if (isset($args[$param->getName()])) {
                            $pass[] = $args[$param->getName()];
                        } else {
                            $pass[] = null;
                        }
                    }
                    $abTestResults = $reflection->invokeArgs($this, $pass);
                }
            }
        }

        // Prepare stats for bargraph
        $variant = ($parent && $parent === $email);
        $stats   = ($email->getEmailType() == 'template') ? $model->getEmailGeneralStats($email, $variant) : $model->getEmailListStats($email, $variant);

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject('email', $email->getId(), $email->getDateAdded());

        // Get click through stats
        $trackableLinks = $model->getEmailClickStats($email->getId());

        return $this->delegateView(
            array(
                'returnUrl'       => $this->generateUrl(
                    'mautic_email_action',
                    array(
                        'objectAction' => 'view',
                        'objectId'     => $email->getId()
                    )
                ),
                'viewParameters'  => array(
                    'email'          => $email,
                    'stats'          => $stats,
                    'trackableLinks' => $trackableLinks,
                    'pending'        => $model->getPendingLeads($email, null, true),
                    'logs'           => $logs,
                    'variants'       => array(
                        'parent'     => $parent,
                        'children'   => $children,
                        'properties' => $properties,
                        'criteria'   => $criteria['criteria']
                    ),
                    'permissions'    => $security->isGranted(
                        array(
                            'email:emails:viewown',
                            'email:emails:viewother',
                            'email:emails:create',
                            'email:emails:editown',
                            'email:emails:editother',
                            'email:emails:deleteown',
                            'email:emails:deleteother',
                            'email:emails:publishown',
                            'email:emails:publishother'
                        ),
                        "RETURN_ARRAY"
                    ),
                    'abTestResults'  => $abTestResults,
                    'security'       => $security,
                    'previewUrl'     => $this->generateUrl(
                        'mautic_email_preview',
                        array('objectId' => $email->getId()),
                        true
                    ),
                    'spamassassinOutput' => $this->forward(
                            'VLNSpamassassincheckerBundle:Spamscore:spamscore',
                            array(
                                'emailId' => $email->getId(),
                                'ignoreAjax' => 1
                                )
                            )->getContent(),
                ),
                'contentTemplate' => 'MauticEmailBundle:Email:details.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_email_index',
                    'mauticContent' => 'email'
                )
            )
        );
    }
}
