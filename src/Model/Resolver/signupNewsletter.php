<?php

/**
 * ScandiPWA_NewsletterGraphQl
 *
 * @category    ScandiPWA
 * @package     ScandiPWA_NewsletterGraphQl
 * @author      Scandesignmedia <info@scandesignmedia.dk>
 * @copyright   Copyright (c) 2019 Scandiweb, Ltd (https://scandiweb.com)
 */

namespace ScandiPWA\NewsletterGraphQl\Model\Resolver;

use Exception;
use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAlreadyExistsException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Phrase;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Store\Model\StoreManagerInterface;
use ScandiPWA\NewsletterGraphQl\Model\Subscriber;
use Magento\Newsletter\Controller\Subscriber\NewAction;


/**
 * Class signupNewsletter
 */
class signupNewsletter extends NewAction implements ResolverInterface
{
    /**
     * @var CustomerAccountManagement
     */
    protected $customerAccountManagement;

    /**
     * @var EmailValidator
     */
    private $emailValidator;
    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;
    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * Initialize dependencies.
     *
     * @param SubscriberFactory $subscriberFactory
     * @param Subscriber $subscriber
     * @param Context $context
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param CustomerUrl $customerUrl
     * @param CustomerAccountManagement $customerAccountManagement
     * @param EmailValidator $emailValidator
     */

    public function __construct(
        SubscriberFactory $subscriberFactory,
        Subscriber $subscriber,
        Context $context,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        CustomerUrl $customerUrl,
        CustomerAccountManagement $customerAccountManagement,
        EmailValidator $emailValidator = null
    )
    {
        $this->subscriberFactory = $subscriberFactory;
        $this->subscriber = $subscriber;

        $this->customerAccountManagement = $customerAccountManagement;
        $this->emailValidator = $emailValidator ?: ObjectManager::getInstance()->get(EmailValidator::class);
        parent::__construct(
            $context,
            $subscriberFactory,
            $customerSession,
            $storeManager,
            $customerUrl,
            $customerAccountManagement
        );
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        $status_text = "";
        $success_text = "";
        if ($args['email']) {
            $email = $args['email'];

            $this->validateEmailFormat($email);
            $this->validateGuestSubscription();
            $this->validateEmailAvailable($email);

            $subscriber = $this->_subscriberFactory->create()->loadByEmail($email);
            if ($subscriber->getId()
                && (int)$subscriber->getSubscriberStatus() === \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
            ) {
                throw new GraphQlAlreadyExistsException(__("This email address already exist"));
            }

            $status = (int)$this->subscriber->subscribeNewsletter($args['email']);


            if ($this->messageManager->addSuccessMessage($this->getSuccessMessage($status))) {
                $success_text = $this->getSuccessMessage($status);
            }

            $status_text = $success_text;
        }

        return [
            'status' => $status_text
        ];
    }

    function getSuccessMessage(int $status): Phrase
    {
        if ($status === \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE) {
            return __('The confirmation request has been sent.');
        }

        return __('Thank you for your subscription.');
    }
}