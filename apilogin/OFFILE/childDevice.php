<?php
function getChildDevice()
{
    $currentFile = basename(__FILE__);
    global $conn;
    try {
        $id = $_GET['id'];
        $sql = "SELECT CONCAT('CHILD'," .
            COLUMN_INVENTORIES_ID . ") as id," .
            COLUMN_INVENTORIES_NAME . " as Name," .
            COLUMN_INVENTORIES_PARENTID . " as ParentId," .
            COLUMN_INVENTORIES_PID . " as PID," .
            COLUMN_INVENTORIES_VID . " as VID," .
            COLUMN_INVENTORIES_SERIAL . " as Serial," .
            COLUMN_INVENTORIES_CDESC . " as CDESC "
            . "FROM " . TABLE_INVENTORIES . "  where " . COLUMN_INVENTORIES_PARENTID . " =:id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $inventories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($inventories);
    } catch (PDOException $th) {
        throw new Error("Error in $currentFile ->" . $th->getMessage());
    } catch (Exception $th) {
        throw new Error("Error in $currentFile ->" . $th->getMessage());
    } catch (Error $th) {
        throw new Error("Error in $currentFile ->" . $th->getMessage());
    }
}
