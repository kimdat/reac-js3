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
    const COLUMN_INVENTORIES_DELETE_AT = 'deleteAt';
}
class constraintStatusDevices
{
    const STATUS_UN_MANAGED = '0';
    const STATUS_MANAGED = '1';
    const STATUS_UNREACH = '2';
    //default là umanagedy
    const STATUS_DEFAUL = self::STATUS_UN_MANAGED;
}
