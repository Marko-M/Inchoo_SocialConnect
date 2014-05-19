<?php

class Inchoo_SocialConnect_TwitterController extends Inchoo_SocialConnect_Controller_Abstract
{

    public function requestAction()
    {
        $client = Mage::getSingleton('inchoo_socialconnect/twitter_client');
        if(!($client->isEnabled())) {
            Mage::helper('inchoo_socialconnect')->redirect404($this);
        }

        try {
            $client->fetchRequestToken();
        } catch (Exception $e) {
            $referer = Mage::getSingleton('core/session')
                ->getSocialConnectRedirect();

            Mage::getSingleton('core/session')->addError($e->getMessage());
            Mage::logException($e);

            $this->_sessionCleanup();

            if(!empty($referer)) {
                $this->_redirectUrl($referer);
            } else {
                Mage::helper('inchoo_socialconnect')->redirect404($this);
            }
        }
    }

    protected function _disconnectCallback(Mage_Customer_Model_Customer $customer) {
        Mage::helper('inchoo_socialconnect/twitter')->disconnect($customer);

        Mage::getSingleton('core/session')
            ->addSuccess(
                $this->__('You have successfully disconnected your Twitter account from our store account.')
            );
    }

    protected function _connectCallback() {
        if (!($params = $this->getRequest()->getParams())
            ||
            !($requestToken = unserialize(Mage::getSingleton('core/session')
                ->getTwitterRequestToken()))
            ) {
            // Direct route access - deny
            return $this;
        }

        if(isset($params['denied'])) {
            Mage::getSingleton('core/session')
                    ->addNotice(
                        $this->__('Twitter Connect process aborted.')
                    );

            return $this;
        }

        $client = Mage::getSingleton('inchoo_socialconnect/twitter_client');

        $token = $client->getAccessToken();

        $userInfo = (object) array_merge(
                (array) ($userInfo = $client->api('/account/verify_credentials.json', 'GET', array('skip_status' => true))),
                array('email' => sprintf('%s@twitter-user.com', strtolower($userInfo->screen_name)))
        );

        $customersByTwitterId = Mage::helper('inchoo_socialconnect/twitter')
            ->getCustomersByTwitterId($userInfo->id);

        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            // Logged in user
            if($customersByTwitterId->count()) {
                // Twitter account already connected to other account - deny
                Mage::getSingleton('core/session')
                    ->addNotice(
                        $this->__('Your Twitter account is already connected to one of our store accounts.')
                    );

                return $this;
            }

            // Connect from account dashboard - attach
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            Mage::helper('inchoo_socialconnect/twitter')->connectByTwitterId(
                $customer,
                $userInfo->id,
                $token
            );

            Mage::getSingleton('core/session')->addSuccess(
                $this->__('Your Twitter account is now connected to your store accout. You can now login using our Twitter Login button or using store account credentials you will receive to your email address.')
            );

            return $this;
        }

        if($customersByTwitterId->count()) {
            // Existing connected user - login
            $customer = $customersByTwitterId->getFirstItem();

            Mage::helper('inchoo_socialconnect/twitter')->loginByCustomer($customer);

            Mage::getSingleton('core/session')
                ->addSuccess(
                    $this->__('You have successfully logged in using your Twitter account.')
                );

            return $this;
        }

        $customersByEmail = Mage::helper('inchoo_socialconnect/twitter')
            ->getCustomersByEmail($userInfo->email);

        if($customersByEmail->count()) {
            // Email account already exists - attach, login
            $customer = $customersByEmail->getFirstItem();

            Mage::helper('inchoo_socialconnect/twitter')->connectByTwitterId(
                $customer,
                $userInfo->id,
                $token
            );

            Mage::getSingleton('core/session')->addSuccess(
                $this->__('We have discovered you already have an account at our store. Your Twitter account is now connected to your store account.')
            );

            return $this;
        }

        // New connection - create, attach, login
        if(empty($userInfo->name)) {
            throw new Exception(
                $this->__('Sorry, could not retrieve your Twitter last name. Please try again.')
            );
        }

        Mage::helper('inchoo_socialconnect/twitter')->connectByCreatingAccount(
            $userInfo->email,
            $userInfo->name,
            $userInfo->id,
            $token
        );

        Mage::getSingleton('core/session')->addSuccess(
            $this->__('Your Twitter account is now connected to your new user accout at our store. Now you can login using our Twitter Login button.')
        );
        Mage::getSingleton('core/session')->addNotice(
            sprintf($this->__('Since Twitter doesn\'t support third-party access to your email address, we were unable to send you your store accout credentials. To be able to login using store account credentials you will need to update your email address and password using our <a href="%s">Edit Account Information</a>.'), Mage::getUrl('customer/account/edit'))
        );
    }

}
