<?php
// START THE SESSION
session_start();

// CONFIGURATION
require("config.php");

// CONNECT TO DB
$pdo = new PDO(
	"mysql:host=$host;dbname=$dbname;charset=$charset", 
	$user, $password, [
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES   => false,
	]
);

// PROCESS REQUESTS
switch ($_POST['request']) {
	case "editar":
                $sql = sprintf("UPDATE `products` SET `product_show` = 0 WHERE product_id = %d",
                        $_POST['product_id']
                        );
		$pdo->exec($sql);
                break;
            
    
        case "add":
		// ITEMS WILL BE STORED IN THE ORDER OF
		// $_SESSION['cart'][PRODUCT ID] = QUANTITY
		if (is_numeric($_SESSION['cart'][$_POST['product_id']])) {
			$_SESSION['cart'][$_POST['product_id']]++;
		} else {
			$_SESSION['cart'][$_POST['product_id']] = 1;
		}
		break;

	// THIS PART COULD BE DONE BETTER BUT I WILL JUST LEAVE IT AS TO SIMPLIFY THINGS
	case "show":
		// FETCH PRODUCTS
		$stmt = $pdo->query('SELECT * FROM `products` WHERE `product_show` = 1');
		$products = array();
		while ($row = $stmt->fetch()){
			$products[$row['product_id']] = $row;
		}

                // VALUES ('%s', '%s', '%d')", 
		//	$_POST['name'], $_POST['desc'], $_POST['price']
                
		// SPIT OUT THE CART IN HTML
		$sub = 0;
		$total = 0; ?>
		<h1>MY CART</h1>
		<table class="table table-striped">
			<tr>
				<th>Cantidad</th>
				<th>Producto</th>
				<th>Precio</th>
			</tr>
			<?php
			foreach($_SESSION['cart'] as $id=>$qty) {
				// CALCULATE THE TOTALS
				$sub = $qty * $products[$id]['product_price'];
				$total += $sub;

				// THE PRODUCT
				printf("<tr><td><input id='qty_%u' onchange='qtyCart(%u);' type='text' value='%u'/></td><td>%s</td><td>$%0.2f</td></tr>",
					$id, $id, $qty,
					$products[$id]['product_name'],
					$sub
				);
			}
			?>
			<tr>
				<td></td>
				<td><strong>Total</strong></td>
				<td><strong>$<?=$total?></strong></td>
			</tr>
			<?php if($total>0){ ?>
			<tr>
				<td colspan="2"></td>
				<td>
					Nombre: <input type="text" id="co_name"/><br><br>
					Email: <input type="text" id="co_email"/><br><br>
					<input type="button" class="btn btn-success" value="Checkout" onclick="cartCheckout();"/>
 				        <!--#000080-->
                                </td>
			</tr>
			<?php } ?>
		</table>
		<?php
		break;

	case "qty":
		if ($_POST['qty']==0) {
			unset($_SESSION['cart'][$_POST['product_id']]);
		} else {
			$_SESSION['cart'][$_POST['product_id']] = $_POST['qty'];
		}
		break;

	// NO ERROR & SECURITY CHECKS IN THIS SIMPLE EXAMPLE...
	// BEEF UP THIS SECTION IF YOU WANT
	case "checkout":
		// CREATE THE ORDER
		$sql = sprintf("INSERT INTO `orders` (`order_name`, `order_email`) VALUES ('%s', '%s')", 
			$_POST['name'], $_POST['email']
		);
		$pdo->exec($sql);
		$last_id = $pdo->lastInsertId();

		// INSERT THE ITEMS
		$sql = "INSERT INTO `orders_items` (`order_id`, `product_id`, `quantity`) VALUES ";
		foreach ($_SESSION['cart'] as $id=>$qty) {
			$sql .= sprintf("(%u,%u,%u),", $last_id, $id, $qty);
		}
		$sql = substr($sql,0,-1);	// STRIP LAST COMMA
		$sql .= ";";
		$pdo->exec($sql);

		// CLEAR OUT THE CURRENT CART
		$_SESSION['cart'] = array();
		break;
                
                case "anadir":
            ?>
            <h1> Agregar un producto </h1>
            <table class="table table-striped">
			<tr>
            <td colspan="2"></td>
				<td>
					Nombre: <input type="text" id="prod_name"/><br><br>
					Descripcion: <input type="text" id="prod_desc"/><br><br>
                                        Precio <input type="number" name="prod_precio" min="1"><br><br>
					<input type="button" class="btn btn-success" value="Crear" onclick="agregar();"/>
 				        <!--#000080-->
                                </td>
            </tr>
                </table>
            <?php
            break;
                
                case "agregarprod":
		// CREATE THE ORDER
		$sql = sprintf("INSERT INTO `products` (`product_name`, `product_description`, `product_price`) VALUES ('%s', '%s', '%d')", 
			$_POST['name'], $_POST['desc'], $_POST['price']
		);
		$pdo->exec($sql);
		break;
}
## Test ##
## INSERT INTO `products` (`product_id`, `product_name`, `product_description`, `product_price`) VALUES (7, 'Test','Marca: Test' ,'1');
## DELETE FROM `products` WHERE product_id = 7
?>