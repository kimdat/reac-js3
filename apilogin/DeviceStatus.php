<?php
class DeviceStatus
{
    const TABLE_DEVICE_STATUS = 'device_status';
    const COLUMN_DEVICESTATUS_ID = 'id';
    const COLUMN_DEVICESTATUS_NAME = 'name';
}

class DeviceTypeTable
{
    const TABLE_DEVICE_TYPE = 'device_type';
    const COLUMN_DEVICE_TYPE_ID = 'id';
    const COLUMN_DEVICE_TYPE_NAME = 'name';
}

class RegionTable
{
    const TABLE_REGION = 'region';
    const COLUMN_REGION_ID = 'id';
    const COLUMN_REGION_NAME = 'name';
    const COLUMN_REGION_ORDER = 'order';
}

class ProvinceTable
{
    const TABLE_PROVINCE = 'province';
    const COLUMN_PROVINCE_ID = 'id';
    const COLUMN_PROVINCE_NAME = 'name';
    const COLUMN_PROVINCE_ORDER = 'order';
    const COLUMN_REGION_ID = 'region_id';
}
