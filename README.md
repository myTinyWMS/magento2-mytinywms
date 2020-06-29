### Overview

This module adds an observer to Magento when an invoice is being created. At this time, it checks the SKU's of the ordered
products against the MyTinyWMS API. If found, a change quantity request will be sent. 

### Install

Use composer to install this module. From your Magento 2 folder run:

```bash
composer require mytinywms/magento2-mytinywms
php bin/magento setup:upgrade 
```

### Usage

- Create an API Token within MyTinyWMS with read and write access for Articles and Article Groups
- Open Magento Admin, go to Stores - Configuration - MyTinyWMS
- Enter API Endpoint
- Enter API Token