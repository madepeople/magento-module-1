<?php
/*
 * This file is part of the Paynova Aero Magento Payment Module, which enables the use of Paynova within the 
 * Magento e-commerce platform.
 *
 * Copyright (C) 2012  Paynova AB
 * 
 * This module is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category    Paynova
 * @package     Paynova_Aero
 */

class Paynova_Paso_Model_Idl extends Paynova_Paso_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'paso_idl';
    protected $_paymentMethod	= 'IDL';
    protected $_selectedPaymentId		= '110';
}
