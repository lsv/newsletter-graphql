<?php

/**
 * ScandiPWA_NewsletterGraphQl
 *
 * @category    ScandiPWA
 * @package     ScandiPWA_NewsletterGraphQl
 * @author      Scandesignmedia <info@scandesignmedia.dk>
 * @copyright   Copyright (c) 2019 Scandiweb, Ltd (https://scandiweb.com)
 */

namespace ScandiPWA\NewsletterGraphQl\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Subscriber model
 *
 * @method $this setSubscriberEmail(string $value)
 * */
class Subscriber extends \Magento\Newsletter\Model\Subscriber
{
    /**
     * Initialize resource model
     *
     * @param $email
     * @param string $subscriber_firstname
     * @param string $subscriber_lastname
     * @param string $subscriber_country_code
     * @return int
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */

    public function subscribeNewsletter($email)
    {
        $this->loadByEmail($email);

        if (!$this->getId()) {
            $this->setSubscriberConfirmCode($this->randomSequence());
        }

        $isConfirmNeed = $this->_scopeConfig->getValue(
            self::XML_PATH_CONFIRMATION_FLAG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) == 1 ? true : false;
        $isOwnSubscribes = false;

        $isSubscribeOwnEmail = $this->_customerSession->isLoggedIn() && $this->_customerSession->getCustomerDataObject()->getEmail() == $email;

        if (!$this->getId() || $this->getStatus() == self::STATUS_UNSUBSCRIBED || $this->getStatus() == self::STATUS_NOT_ACTIVE
        ) {
            if ($isConfirmNeed === true) {
                // if user subscribes own login email - confirmation is not needed
                $isOwnSubscribes = $isSubscribeOwnEmail;
                if ($isOwnSubscribes == true) {
                    $this->setStatus(self::STATUS_SUBSCRIBED);
                } else {
                    $this->setStatus(self::STATUS_NOT_ACTIVE);
                }
            } else {
                $this->setStatus(self::STATUS_SUBSCRIBED);
            }

            $this->setSubscriberEmail($email);
        }

        if ($isSubscribeOwnEmail) {
            try {
                $customer = $this->customerRepository->getById($this->_customerSession->getCustomerId());
                $this->setStoreId($customer->getStoreId());
                $this->setCustomerId($customer->getId());
            } catch (NoSuchEntityException $e) {
                $this->setStoreId($this->_storeManager->getStore()->getId());
                $this->setCustomerId(0);
            }
        } else {
            $this->setStoreId($this->_storeManager->getStore()->getId());
            $this->setCustomerId(0);
        }

        $this->setStatusChanged(true);

        try {
            $this->save();
            if ($isConfirmNeed === true && $isOwnSubscribes === false
            ) {
                $this->sendConfirmationRequestEmail();
            } else {
                $this->sendConfirmationSuccessEmail();
            }
            return $this->getStatus();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Sends out confirmation success email
     *
     * @return void
     */
    public function sendConfirmationSuccessEmail()
    {
    }

    /**
     * Sends out unsubscription email
     *
     * @return void
     */
    public function sendUnsubscriptionEmail()
    {
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSubscriberFirstname(): string
    {
        if ($this->getDataByKey('customer_id')) {
            $_customer = $this->customerRepository->getById($this->getDataByKey('customer_id'));
            $getFirstname = $_customer->getFirstname() ? $_customer->getFirstname() : $this->getDataByKey('subscriber_firstname');

        } else {
            $getFirstname = $this->getDataByKey('subscriber_firstname');

        }

        if ($firstname = $getFirstname) {
            return ucfirst($firstname);
        }

        return __('Guest');
    }
}
