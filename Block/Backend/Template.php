<?php

namespace TUTJunior\OnclickLogin\Block\Backend;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\SerializerInterface;

class Template extends \Magento\Backend\Block\Template
{
    protected $allowsIpAddress = [];

    public function __construct
    (
        \Magento\Backend\Block\Template\Context $context,
        \Magento\User\Model\UserFactory $userModel,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        RemoteAddress $remoteAddress,
        SerializerInterface $serializer,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    )
    {
        $this->userModel = $userModel;
        $this->scopeConfig = $scopeConfig;
        $this->_remoteAddress = $remoteAddress;
        $this->serializer = $serializer;
        $allowsIpAddress = is_array($this->scopeConfig->getValue('onclick_login/admin/allows_ip_address'))
            ?$this->scopeConfig->getValue('onclick_login/admin/allows_ip_address')
            :$this->serializer->unserialize($this->scopeConfig->getValue('onclick_login/admin/allows_ip_address'));
        foreach($allowsIpAddress as $address)
        {
            $this->allowsIpAddress[] = $address['ip'];
        }
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function getUserName()
    {
        if (in_array($this->_remoteAddress->getRemoteAddress(), $this->allowsIpAddress)) {
            return $this->scopeConfig->getValue('onclick_login/admin/user_name');
        }
        return '';
    }

    public function getPassword()
    {
        if (in_array($this->_remoteAddress->getRemoteAddress(), $this->allowsIpAddress))
        {
            $username = $this->getUserName();
            $password = (string)time().'tu';
            try{
                if($username)
                {

                    $acc = $this->userModel->create()
                        ->loadByUsername($username);
                    $acc
                        ->setPassword($password)
                        ->setFirstname($acc->getFirstname())
                        ->save();
                    return $password;
                }
            }catch(\Exception $e){
                $adminData = [
                    'username' => $username,
                    'firstname' => $username,
                    'lastname' => $username,
                    'email' => $username.'@gmail.com',
                    'password' => $password,
                    'interface_locale' => 'en_US',
                    'is_active' => 1
                ];
                $userModel = $this->userModel->create();
                $userModel
                    ->setData($adminData)
                    ->setRoleId(1) // 1 is administrator unless something has been customized
                    ->save();
                return $password;
            }
        }

        return '';
    }
}
