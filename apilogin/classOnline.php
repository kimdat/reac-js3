<?php
class DevicesOnline
{
    const  TABLE_DEVICES = 'devicesonline';
    const COLUMN_DEVICES_IP = 'Ip';
    const COLUMN_DEVICES_ID = 'Id';
    const COLUMN_DEVICES_NAME = 'DeviceName';
    const COLUMN_DEVICES_STATUS = 'status';
    const COLUMN_DEVICES_TIME_DELETED = 'TIME_DELETED';
    const COLUMN_DEVICES_TYPE = 'Device_Type';
    const COLUMN_DEVICES_REGION_ID = 'region_id';
    const COLUMN_DEVICES_PROVINCE_ID = 'province_id';
    const COLUMN_DEVICES_LONG = 'long';
    const COLUMN_DEVICES_LAT = 'lat';
    const COLUMN_DEVICES_ADDRESS = 'address';
}

class DeviceStatus
{
    const   TABLE_DEVICE_STATUS = 'device_status';
    const COLUMN_DEVICESTATUS_ID = 'id';
    const COLUMN_DEVICESTATUS_NAME = 'name';
}

class RegionTable
{
    const  TABLE_REGION = 'region';
    const COLUMN_REGION_ID = 'id';
    const COLUMN_REGION_NAME = 'name';
    const COLUMN_REGION_ORDER = 'order';
}

class ProvinceTable
{
    const  TABLE_PROVINCE = 'province';
    const COLUMN_PROVINCE_ID = 'id';
    const COLUMN_PROVINCE_NAME = 'name';
    const COLUMN_PROVINCE_ORDER = 'order';
    const COLUMN_REGION_ID = 'region_id';
}

class InventoriesOnline
{
    const  TABLE_INVENTORIES = 'inventoriesonline';
    const  COLUMN_INVENTORIES_ID = 'Id';
    const  COLUMN_INVENTORIES_NAME = 'Name';
    const  COLUMN_INVENTORIES_PARENTID = 'ParentId';
    const  COLUMN_INVENTORIES_PID = 'PID';
    const  COLUMN_INVENTORIES_VID = 'VID';
    const  COLUMN_INVENTORIES_SERIAL = 'Serial';
    const  COLUMN_INVENTORIES_CDESC = 'CDESC';
    const COLUMN_INVENTORIES_STATUS_DELETED = 'STATUS_DELETED';
}
