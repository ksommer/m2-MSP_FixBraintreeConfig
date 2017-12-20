<?php
/**
 * Created by PhpStorm.
 * User: andrea
 * Date: 15/12/17
 * Time: 11.49
 */
namespace MSP\FixBraintreeConfig\Plugin\Model\Adminhtml\System\Config;

class CountryCreditCardPlugin
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->logger = $logger;
    }

    public function aroundBeforeSave(\Magento\Braintree\Model\Adminhtml\System\Config\CountryCreditCard $subject, callable $proceed)
    {
        $proceed();

        $value = null;
        try {
            $value = $this->serializer->unserialize($this->getValue());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $result = [];
        if (is_array($value)) {
            foreach ($value as $data) {
                if (empty($data['country_id']) || empty($data['cc_types'])) {
                    continue;
                }
                $country = $data['country_id'];
                if (array_key_exists($country, $result)) {
                    $result[$country] = $subject->appendUniqueCountries($result[$country], $data['cc_types']);
                } else {
                    $result[$country] = $data['cc_types'];
                }
            }
        }
        $subject->setValue($this->serializer->serialize($result));
        return $subject;
    }
}
