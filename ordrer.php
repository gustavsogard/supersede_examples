<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    $_SESSION["requested_url"] = "$_SERVER[REQUEST_URI]";
    header("location: /log-ind");
    exit;
}

require_once "config.php";

$user_id = $_SESSION["user_id"];
$company_id = $_SESSION["company_id"];

$sortby_status = $_GET["status"];
$sortby_supplier = $_GET["supplier"];

$message = $_GET["message"];

if (isset($_GET["supplier"]) && $sortby_supplier !== "") {
    $check = mysqli_query($link, "SELECT supplier_id FROM Suppliers WHERE company_id=$company_id");
    while ($row_check = mysqli_fetch_row($check)[0]) {
        if ($row_check == $sortby_supplier) {
            $flag = 1;
        }
    }
    if ($flag !== 1) {
        header("location: /ordrer.php?message=3");
        exit();
    }
}

if ((!isset($_GET["page"]) || !is_numeric($_GET["page"])) && isset($_GET["message"])) {
    header("location: /ordrer?page=1&message=$message");
    exit();
} else if (!isset($_GET["page"]) || !is_numeric($_GET["page"]) || $_GET["page"] < 1) {
    header("location: /ordrer?page=1");
    exit();
}

if (isset($_GET["interval"]) && ($_GET["interval"] !== "1" && $_GET["interval"] !== "7" && $_GET["interval"] !== "30" && $_GET["interval"] !== "ytd" && $_GET["interval"] !== "")) {
    header("location: /ordrer?page=1&message=3");
    exit();
}

if ($_GET["interval"] == "ytd") {
    $sortby_interval = date("Y");
} else {
    $sortby_interval = $_GET["interval"];
}

$_GET["page"] = intval($_GET["page"]);

$title = "Ordrer - Supersede";
include "header.php";

$orders_sql = "SELECT * FROM Orders WHERE company_id=$company_id";

?>

<style>
.main_box {
    display: block;
    max-width: 600px;
    min-width: 500px;
    align-items: center;
    margin: auto;
    padding: 0px 10%;
}

.box_temp {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    padding: 30px;
    margin: 20px;
    border: 1px solid rgb(221, 221, 221);
    border-radius: 30px;
    background-color: rgb(245, 245, 245);
}

.label {
    padding: 2px 6px;
    width: 120px;
    text-align: center;
    text-transform: uppercase;
    font-size: 12px;
    color: white;
    border-radius: 8px;
    margin: 0;
    cursor: default;
}

.lbl_PENDING {
    background-color: #B4B4B4;
}
.lbl_PENDING_PAYMENT {
    background-color: #d69d00;
}
.lbl_PENDING_TRACKING {
    background-color: #5c5c5c;
}
.lbl_PENDING_COMPLETE {
    background-color: #93ad9b;
    font-size: 11px;
}
.lbl_COMPLETED {
    background-color: #57A76D;
}
.lbl_CANCELLED {
    background-color: #D73E3E;
}

.ordre-titel {
    font-size: 18px;
    font-weight: 700;
    margin: 10px 0px 20px 0px;
}
.ordre-titel:hover {
    font-weight: 800;
}

td {
    padding-right: 20px;
    padding-left: 0;
    font-size: 14px;
}

table {
    border-spacing: 0;
}

#invoice {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    text-align: center;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    transition: all 0.3s ease 0s;
}

.no-invoice {
    border: 2px solid #be0032;
    color: #be0032;
}
.no-invoice:hover {
    background-color: #eeeeee;
    cursor: default;
}

.yes-invoice {
    background-color: #57A76D;
    color:white;
    border: 2px solid #408753;
}
.yes-invoice:hover {
    background-color: #408753;
    cursor: pointer;
}

.yes-invoice-cancelled {
    background-color: #D73E3E;
    color:white;
    border: 2px solid #be0032;
}
.yes-invoice-cancelled:hover {
    background-color: #be0032;
    cursor: pointer;
}

</style>

<div class='main_box'>
    <h3 style="text-align: center; margin-top:30px; margin-bottom:5px;">Ordrer</h3>
    <p style="text-align: center; margin-top:0px; margin-bottom:30px; font-size: 14px;">Følg og se din virksomheds ordrer til leverandører</p>
</div>

<?php

if ($sortby_status == 'pending') {
    $orders_sql .= " AND status='pending'";
} else if ($sortby_status == 'pending_payment') {
    $orders_sql .= " AND status='pending_payment'";
} else if ($sortby_status == 'pending_tracking') {
    $orders_sql .= " AND status='pending_tracking'";
} else if ($sortby_status == 'pending_complete') {
    $orders_sql .= " AND status='pending_complete'";
} else if ($sortby_status == 'completed') {
    $orders_sql .= " AND status='completed'";
} else if ($sortby_status == 'cancelled') {
    $orders_sql .= " AND status='cancelled'";
}
if (isset($sortby_supplier) && $sortby_supplier !== "" && $sortby_supplier) {
    $orders_sql .= " AND sent_to IN (SELECT contact_id FROM Contacts WHERE supplier_id='$sortby_supplier')";
}
if (isset($sortby_interval) && $_GET["interval"] == "ytd") {
    $orders_sql .= " AND timestamp > '$sortby_interval'";
} else if (isset($sortby_interval)) {
    $days = $_GET["interval"];
    if ($days !== "") {
        $orders_sql .= " AND timestamp > NOW() - INTERVAL $days DAY";
    }
}

$orders_sql .= " ORDER BY timestamp DESC";

$num_orders_total = mysqli_num_rows(mysqli_query($link, $orders_sql));
if ($num_orders_total != 1) {
    $num_orders_total .= " ordrer";
} else {
    $num_orders_total .= " ordre";
}

if (isset($_GET["page"]) && is_numeric($_GET["page"])) {
    $interval_start = ($_GET["page"] - 1) * 20;
    $step = 20;
    $orders_sql .= " LIMIT $interval_start, $step";
}

if ($res_orders = mysqli_query($link, $orders_sql)) {
    $num_orders = mysqli_num_rows($res_orders);
}

if ($num_orders != 1) {
    $num_orders .= " ordrer";
} else {
    $num_orders .= " ordre";
}

?>

<div class="main_box">
    <div style='display:flex; align-items:center; justify-content:space-between; margin-left:20px; margin-right:20px;'>
        <form action="/ordrer.php" method="GET" style='display:flex;'>
            <select name="status" class='form-control' style='width:80px; padding:5px; margin:0; margin-right:5px; color:#a1a1a1'>
                <option value="" disabled <?php if (!isset($_GET["status"])) {echo "selected";} ?>>Status</option>
                <option value="" <?php if (isset($_GET["status"]) && $_GET["status"] == "") {echo "selected";} ?>>Alle</option>
                <option value="pending" <?php if (isset($_GET["status"]) && $_GET["status"] == "pending") {echo "selected";} ?>>Afventer faktura</option>
                <option value="pending_payment" <?php if (isset($_GET["status"]) && $_GET["status"] == "pending_payment") {echo "selected";} ?>>Afventer betaling</option>
                <option value="pending_tracking" <?php if (isset($_GET["status"]) && $_GET["status"] == "pending_tracking") {echo "selected";} ?>>Afventer trackingnummer</option>
                <option value="pending_complete" <?php if (isset($_GET["status"]) && $_GET["status"] == "pending_complete") {echo "selected";} ?>>Tracking modtaget</option>
                <option value="completed" <?php if (isset($_GET["status"]) && $_GET["status"] == "completed") {echo "selected";} ?>>Gennemført</option>
                <option value="cancelled" <?php if (isset($_GET["status"]) && $_GET["status"] == "cancelled") {echo "selected";} ?>>Annulleret</option>
            </select>
            <select name="supplier" class='form-control' style='width:110px; padding:5px; margin:0; margin-right:5px; color:#a1a1a1'>
                <option value="" disabled <?php if (!isset($_GET["supplier"])) {echo "selected";} ?>>Leverandør</option>
                <option value="" <?php if (isset($_GET["supplier"]) && $_GET["supplier"] == "") {echo "selected";} ?>>Alle</option>
                <?php
                $res_suppliers = mysqli_query($link, "SELECT * FROM Suppliers WHERE company_id=$company_id AND visibility='shown'");
                while ($row_supplier = mysqli_fetch_row($res_suppliers)) {
                    echo "<option value='$row_supplier[0]'"; if (isset($_GET["supplier"]) && $_GET["supplier"] == "$row_supplier[0]") {echo "selected";} echo ">$row_supplier[1]</option>";
                }
                ?>
            </select>
            <select name="interval" class="form-control" style='width:85px; padding:5px; margin:0; margin-right:5px; color:#a1a1a1'>
                <option value="" disabled <?php if (!isset($_GET["interval"])) {echo "selected";} ?>>Interval</option>
                <option value="" <?php if (isset($_GET["interval"]) && $_GET["interval"] == "") {echo "selected";} ?>>Alle</option>
                <option value="1" <?php if (isset($_GET["interval"]) && $_GET["interval"] == "1") {echo "selected";} ?>>I dag</option>
                <option value="7" <?php if (isset($_GET["interval"]) && $_GET["interval"] == "7") {echo "selected";} ?>>Sidste 7 dage</option>
                <option value="30" <?php if (isset($_GET["interval"]) && $_GET["interval"] == "30") {echo "selected";} ?>>Sidste 30 dage</option>
                <option value="ytd" <?php if (isset($_GET["interval"]) && $_GET["interval"] == "ytd") {echo "selected";} ?>>År til dato</option>
            </select>
            <input name="page" value="1" hidden>
            <button class='btn btn-tertiary' style='padding:6px; margin:0; border-radius:8px;' type='submit'><span style='font-size:16px; display:flex; color:#a1a1a1' class='material-symbols-rounded'>arrow_right</span></button>
        </form>
        <div style="display:flex; align-items:center; justify-content:center; vertical-align:middle">
            <p style='color:#a1a1a1; font-size:14px; margin-right:15px'><?php echo $num_orders_total; ?> </p>
            <form action="/download_pdf.php" method="POST" target="_blank" style='display:flex;'>
                <input name="status" type="text" value="<?php echo $sortby_status?>" hidden>
                <input name="supplier" type="text" value="<?php echo $sortby_supplier?>" hidden>
                <input name="interval" type="text" value="<?php echo $_GET["interval"]?>" hidden>
                <button class='btn btn-tertiary' style='padding:6px; margin:0; border-radius:8px;' type='submit'><span style='font-size:16px; display:flex; color:#a1a1a1' class='material-symbols-rounded'>file_download</span></button>
            </form>
        </div>
    </div>
    <?php
    if (isset($_GET['message']) && $_GET['message'] == 1) {
    echo "    
            <div style='width:100%; display:flex; justify-content:center; padding-top:20px'>
                <p style='margin:0; border: 2px dotted #be0032; border-radius:10px; display:flex; vertical-align:middle; padding:10px; text-align:center; font-size:12px; background-color:#eeeeee; align-items:center'>
                    Der var en fejl ved oprettelse af ordren.
                    <button id='hide' style='all:unset;cursor:pointer;'>
                        <span style='font-size: 16px; padding:5px' class='material-symbols-rounded'>close</span>
                    </button>
                </p>
            </div>";
    } else if (isset($_GET['message']) && $_GET['message'] == 2) {
        echo "
            <div style='width:100%; display:flex; justify-content:center; padding-top:20px'>
                <p style='margin:0; border: 2px dotted #57A76D; border-radius:10px; display:flex; vertical-align:middle; padding:10px; text-align:center; font-size:12px; background-color:#eeeeee; align-items:center'>
                    Ordren er oprettet og sendt til leverandøren!
                    <button id='hide' style='all:unset;cursor:pointer;'>
                        <span style='font-size: 16px; padding:5px' class='material-symbols-rounded'>close</span>
                    </button>
                </p>
            </div>";
    } else if (isset($_GET['message']) && $_GET['message'] == 3) {
        echo "
            <div style='width:100%; display:flex; justify-content:center; padding-top:20px'>
                <p style='margin:0; border: 2px dotted #be0032; border-radius:10px; display:flex; vertical-align:middle; padding:10px; text-align:center; font-size:12px; background-color:#eeeeee; align-items:center'>
                    Der skete en fejl.
                    <button id='hide' style='all:unset;cursor:pointer;'>
                        <span style='font-size: 16px; padding:5px' class='material-symbols-rounded'>close</span>
                    </button>
                </p>
            </div>
        ";
    } else if (isset($_GET['message']) && $_GET['message'] == 4) {
        echo "
            <div style='width:100%; display:flex; justify-content:center; padding-top:20px'>
                <p style='margin:0; border: 2px dotted #be0032; border-radius:10px; display:flex; vertical-align:middle; padding:10px; text-align:center; font-size:12px; background-color:#eeeeee; align-items:center'>
                    Du er nået dit maksimum antal ordrer om måneden, <br>og ordren blev derfor ikke sendt til leverandøren!
                    <button id='hide' style='all:unset;cursor:pointer;'>
                        <span style='font-size: 16px; padding:5px' class='material-symbols-rounded'>close</span>
                    </button>
                </p>
            </div>
        ";
    } else if (isset($_GET['message']) && $_GET['message'] == 5) {
        echo "
            <div style='width:100%; display:flex; justify-content:center; padding-top:20px'>
                <p style='margin:0; border: 2px dotted #be0032; border-radius:10px; display:flex; vertical-align:middle; padding:10px; text-align:center; font-size:12px; background-color:#eeeeee; align-items:center'>
                    Du mangler at tilføje en faktureringsadresse til din virksomhed i indstillinger.
                    <button id='hide' style='all:unset;cursor:pointer;'>
                        <span style='font-size: 16px; padding:5px' class='material-symbols-rounded'>close</span>
                    </button>
                </p>
            </div>
        ";
    }

        if ($res_orders) {
            if (mysqli_num_rows($res_orders) > 0) {
                while ($row_order = mysqli_fetch_row($res_orders)) {
                    if ($row_order[1] == 'PENDING') {
                        $status = 'Afventer faktura';
                    } else if ($row_order[1] == 'PENDING_PAYMENT') {
                        $status = 'Afventer betaling';
                    } else if ($row_order[1] == 'PENDING_TRACKING') {
                        $status = 'Afventer trackingnummer';
                    } else if ($row_order[1] == 'PENDING_COMPLETE') {
                        $status = 'Tracking modtaget';
                    } else if ($row_order[1] == 'COMPLETED') {
                        $status = 'Gennemført';
                    } else if ($row_order[1] == 'CANCELLED') {
                        $status = 'Annulleret';
                    }
                    $row_products_in_order = mysqli_fetch_all(mysqli_query($link, "SELECT * FROM Order_Products WHERE order_id='$row_order[0]'", MYSQLI_ASSOC));
                    $first_product_id = $row_products_in_order[0][1];
                    echo "
                    <div class='box_temp'>
                        <div style='max-width:80%'>
                            <p class='label lbl_$row_order[1]'>$status</p>
                            <a href='/vis-ordre.php?order_id=$row_order[0]' style='all:unset; cursor:pointer'><p class='ordre-titel'>Ordre $row_order[0]</p></a>
                            <table>
                                <tr>
                                    <td style='padding-bottom: 10px;'>Leverandør: </td>
                                    <td style='padding-bottom: 10px; font-weight: 700'>";
                                    $supplier_name = mysqli_fetch_row(mysqli_query($link, "SELECT supplier_name FROM Suppliers WHERE supplier_id IN (SELECT supplier_id FROM Products WHERE product_id=$first_product_id)"))[0];
                                    echo htmlspecialchars($supplier_name),"</td>
                                </tr>
                                <tr>
                                    <td style='padding-bottom: 10px;'>";
                                    if (count($row_products_in_order) > 1) {
                                        $product_stmt = "Produkter";
                                    } else {
                                        $product_stmt = "Produkt";
                                    }
                                    echo "$product_stmt: </td>

                                    <td style='padding-bottom: 10px; font-weight: 700;'>";
                                    if (count($row_products_in_order) > 1) {
                                        $count = 0;
                                        foreach ($row_products_in_order as $row_product) {
                                            if ($count === 3) {
                                                echo "<br>...";
                                                break;
                                            }
                                            $product_name = mysqli_fetch_row(mysqli_query($link, "SELECT product_name FROM Products WHERE product_id=$row_product[1]"))[0];
                                            if ($count == (count($row_products_in_order)-1)) {
                                            echo htmlspecialchars($product_name);
                                            } else {
                                                echo htmlspecialchars($product_name), ", ";
                                            }
                                            $count++;
                                        }
                                    } else {
                                        $product_name = mysqli_fetch_row(mysqli_query($link, "SELECT product_name FROM Products WHERE product_id=$first_product_id"));
                                        echo htmlspecialchars($product_name[0]);
                                    }
                                    echo "
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding-bottom: 15px;'>Subtotal: </td>
                                    <td style='padding-bottom: 15px; font-weight: 700'>";
                                    
                                    $subtotals = array();
                                    foreach ($row_products_in_order as $row_product) {
                                        $product_details = mysqli_fetch_row(mysqli_query($link, "SELECT * FROM Products WHERE product_id='$row_product[1]'"));
                                        $currencies[] = $product_details[4];
                                        $subtotals[$product_details[4]] += $product_details[3] * $row_product[2];
                                    }

                                    foreach ($subtotals as $currency => $value) {
                                        echo htmlspecialchars($currency)." ".number_format($value, 2, ",", ".")."<br>";
                                    }

                                echo "</td>
                                </tr>
                                <tr>
                                    <td style='font-size:12px; color: #5c5c5c'>"; $time = str_replace("may", "maj", str_replace("oct", "okt", strtolower(date("d. M Y", strtotime($row_order[2]))))); echo "$time</td>
                                </tr>
                            </table>
                        </div>
                        <div style='justify-content: center; display: flex; width: 18%; flex-wrap: wrap;'>
                            <a style='width:100%; height:10px; text-align:center' href='/vis-ordre.php?order_id=$row_order[0]'><button class='btn btn-tertiary' style='width:100%; border-radius:16px; padding: 10px 0px'>Vis</button></a>
                            <span id='invoice'";
                            $invoice_row = mysqli_query($link, "SELECT * FROM Invoices WHERE order_id=$row_order[0]");
                            if (mysqli_num_rows($invoice_row) > 0) {
                                if ($row_order[1] == 'CANCELLED') {
                                    echo "class='yes-invoice-cancelled'";
                                } else {
                                    echo "class='yes-invoice'";
                                }
                            } else {
                                echo "class='no-invoice'";
                            }
                            echo ">
                                <a style='all:unset;' ";
                                if (mysqli_num_rows($invoice_row) > 0) {
                                    $invoice_path = mysqli_fetch_row($invoice_row)[1];
                                    echo "href='/uploads/$invoice_path' target='_blank'";
                                }
                                echo "><span>
                                    <p style='margin:0; margin-bottom:10px; font-size:12px; text-transform:uppercase; width:100%;'>Faktura</p>
                                    <span style='font-size:35px; margin:0;' class='material-symbols-rounded'>file_open</span>
                                </span></a>
                            </span>
                        </div>
                    </div>
                    ";
                }

                if (isset($_GET["status"])) {
                    $hidden_inputs = "<input name='status' value='$sortby_status' hidden>";
                }
                if (isset($_GET["supplier"])) {
                    $hidden_inputs .= "<input name='supplier' value='$sortby_supplier' hidden>";
                }
                if (isset($_GET["interval"])) {
                    $sortby_interval = $_GET["interval"];
                    $hidden_inputs .= "<input name='interval' value='$sortby_interval' hidden>";
                }

                if ($num_orders == 1 && $_GET["page"] == 1) {
                    echo "";
                } else if ($num_orders > 19 && $_GET["page"] == 1) {
                    echo "<div style='width:90%; display:flex; justify-content:end; margin:auto; margin-top:30px'><div style='display:flex; justify-content:center; align-items:center; width:100%'><p style='color:#a1a1a1; font-size:12px; margin-left:15px'>$num_orders</p></div><form action'/ordrer.php' method='GET'><button class='btn btn-tertiary' style='padding:10px' type='submit'><span class='material-symbols-rounded' style='vertical-align: middle;'>forward</span></button>".$hidden_inputs."<input name='page' value='2' hidden></form></div>";
                } else if ($num_orders < 20 && $_GET["page"] !== 1 || $num_orders * $_GET["page"] == $num_orders_total && $_GET["page"] !== 1) {
                    $prev_page = $_GET["page"] - 1;
                    echo "<div style='width:90%; display:flex; justify-content:space-between; margin:auto; margin-top:30px'>
                            <form action'/ordrer.php' method='GET'>
                                <button class='btn btn-tertiary' style='padding:10px' type='submit'>
                                    <span class='material-symbols-rounded' style='vertical-align: middle;'>reply</span>
                                </button>".
                                $hidden_inputs."
                                <input name='page' value='$prev_page' hidden>
                            </form>
                            <div style='display:flex; justify-content:center; align-items:center; width:100%'><p style='color:#a1a1a1; font-size:12px; margin-right:15px'>$num_orders</p>
                            </div>
                        </div>";
                } else if ($num_orders == 20 && $_GET["page"] !== 1) {
                    $prev_page = $_GET["page"] - 1;
                    $next_page = $_GET["page"] + 1;
                    echo "<div style='width:90%; display:flex; justify-content:space-between; margin:auto; margin-top:30px'>
                            <form action'/ordrer.php' method='GET'>
                                <button class='btn btn-tertiary' style='padding:10px' type='submit'>
                                    <span class='material-symbols-rounded' style='vertical-align: middle;'>reply</span>
                                </button>".
                                $hidden_inputs."
                                <input name='page' value='$prev_page' hidden>
                            </form>
                            <div style='display:flex; justify-content:center; align-items:center; width:100%'><p style='color:#a1a1a1; font-size:12px'>$num_orders</p>
                            </div>
                            <form action'/ordrer.php' method='GET'>
                                <button class='btn btn-tertiary' style='padding:10px' type='submit'>
                                    <span class='material-symbols-rounded' style='vertical-align: middle;'>forward</span>
                                </button>"
                                .$hidden_inputs."
                                <input name='page' value='$next_page' hidden></form></div>";
                } else if ($num_orders < 20 && $_GET["page"] == 1) {
                    echo "";
                }
            } else if (mysqli_num_rows($res_orders) == 0) {
                echo "<div class='box_temp' style='text-align:center; justify-content: center; padding: 10px;'><p style='display: inline; width: 100%; margin:20px;'>Ingen ordrer.</p><a href='/ny-ordre'><button style='margin-bottom: 20px;' class='btn btn-primary'>Opret ordre</button></a></div>";
            }
        }
    ?>
</div>

<script>
$(document).ready(
    $('#hide').on('click', function() {
        $(this).parent().parent().hide();
    })
);
</script>

<?php
include "footer.php";
?>
