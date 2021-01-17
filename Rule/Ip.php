<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\Validator\Rule;

use Arikaim\Core\Validator\Rule;

/**
 * Ip address validatiion rule. 
 */
class Ip extends Rule
{    
    /**
     * Constructor
     *
     * @param array $params 
     * @param string|null $error 
     */
    public function __construct(array $params = [], ?string $error = null) 
    {
        parent::__construct($params,$error);
        
        $this->setDefaultError('INT_NOT_VALID_ERROR');
    }

    /**
     * Return filter type
     *
     * @return mixed
     */
    public function getType()
    {       
        return FILTER_VALIDATE_IP;
    }
}
