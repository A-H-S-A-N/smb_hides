<?php
require __DIR__ . '/config.php';
$db = db();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (!admin_logged_in()) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (hash_equals(ADMIN_USERNAME, $_POST['username'] ?? '') &&
            hash_equals(ADMIN_PASSWORD, $_POST['password'] ?? '')) {
            $_SESSION['smb_admin'] = true;
            header('Location: admin.php');
            exit;
        }
        $error = 'Invalid username or password.';
    }
    ?>
    <!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SMB HIDES Admin Login</title>
    <style>body{margin:0;background:#090909;color:#eee;font-family:Arial;min-height:100vh;display:grid;place-items:center}.box{width:360px;max-width:90%;padding:30px;background:#151311;border:1px solid #4a3a25}.logo{display:block;width:120px;margin:0 auto 20px}.field{width:100%;box-sizing:border-box;padding:13px;margin:7px 0;background:#1c1916;border:1px solid #41372b;color:white}.btn{width:100%;padding:13px;margin-top:10px;background:#dfb96e;border:0;font-weight:bold}</style></head>
    <body><div class="box"><img src="logo.png" class="logo"><h2>Admin Panel</h2><?php if($error): ?><p style="color:#e08b70"><?=e($error)?></p><?php endif; ?><form method="post"><input class="field" name="username" placeholder="Username" required><input class="field" name="password" type="password" placeholder="Password" required><button class="btn">LOGIN</button></form></div></body></html>
    <?php exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'save_product') {
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $category=trim($_POST['category']??'Jackets');
    $description=trim($_POST['description']??'');
    $price=(float)($_POST['price']??0);
    $sale=$_POST['sale_price']!=='' ? (float)$_POST['sale_price'] : null;
    $sizes=trim($_POST['sizes']??'S,M,L,XL,XXL');
    $image=trim($_POST['image']??'');
    $badge=trim($_POST['badge']??'');
    $stock=max(0,(int)($_POST['stock']??0));
    $active=isset($_POST['active'])?1:0;

    if ($id) {
        $stmt=$db->prepare("UPDATE products SET name=?,category=?,description=?,price=?,sale_price=?,sizes=?,image=?,badge=?,stock=?,active=? WHERE id=?");
        $stmt->execute([$name,$category,$description,$price,$sale,$sizes,$image,$badge,$stock,$active,$id]);
    } else {
        $stmt=$db->prepare("INSERT INTO products(name,category,description,price,sale_price,sizes,image,badge,stock,active) VALUES(?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$name,$category,$description,$price,$sale,$sizes,$image,$badge,$stock,$active]);
    }
    header('Location: admin.php?tab=products&saved=1'); exit;
}

if ($action === 'delete_product') {
    $id=(int)$_GET['id'];
    $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    header('Location: admin.php?tab=products'); exit;
}

if ($action === 'status') {
    $allowed=['New','Verified','Processing','Shipped','Delivered','Cancelled'];
    $status=$_POST['status']??'New';
    if (in_array($status,$allowed,true)) {
        $db->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status,(int)$_POST['id']]);
    }
    header('Location: admin.php?tab=orders'); exit;
}

$tab=$_GET['tab']??'dashboard';
$orders=$db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$products=$db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$stats=[
    'products'=>(int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders'=>(int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'new'=>(int)$db->query("SELECT COUNT(*) FROM orders WHERE status='New'")->fetchColumn(),
    'sales'=>(float)$db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='Cancelled'")->fetchColumn()
];
$edit=null;
if (isset($_GET['edit'])) {
    $s=$db->prepare("SELECT * FROM products WHERE id=?");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SMB HIDES Admin</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#090909;color:#eee;font-family:Arial,sans-serif}.top{height:70px;background:#111;display:flex;align-items:center;justify-content:space-between;padding:0 25px;border-bottom:1px solid #2a241d}.top img{height:50px}.top a{color:#dfb96e;text-decoration:none;margin-left:18px}.wrap{max-width:1300px;margin:auto;padding:25px}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:15px}.card{background:#151311;border:1px solid #302920;padding:22px}.card b{display:block;color:#dfb96e;font-size:28px;margin-top:8px}.tabs{display:flex;gap:8px;margin:25px 0;flex-wrap:wrap}.tabs a{padding:10px 16px;background:#171411;color:#ccc;text-decoration:none;border:1px solid #2f281f}.tabs a.active{background:#dfb96e;color:#111}.tablewrap{overflow:auto;background:#12110f;border:1px solid #302920}table{border-collapse:collapse;width:100%;min-width:900px}th,td{padding:13px;border-bottom:1px solid #29241e;text-align:left;vertical-align:top}th{color:#dfb96e;background:#171411}.status{display:inline-block;padding:5px 8px;background:#2a241c;color:#dfb96e;font-size:11px}.btn{background:#dfb96e;color:#111;border:0;padding:11px 16px;font-weight:bold;text-decoration:none;cursor:pointer}.danger{color:#e39a7c}.formbox{background:#151311;border:1px solid #302920;padding:25px;margin-bottom:25px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.field{width:100%;padding:12px;background:#1c1916;color:#fff;border:1px solid #40372e}.full{grid-column:1/-1}textarea.field{min-height:100px}.product-list{display:grid;grid-template-columns:repeat(3,1fr);gap:15px}.p{background:#151311;border:1px solid #302920;padding:15px}.p img{width:100%;aspect-ratio:4/5;object-fit:cover}.actions{display:flex;gap:8px;margin-top:10px}@media(max-width:800px){.cards{grid-template-columns:1fr 1fr}.grid{grid-template-columns:1fr}.full{grid-column:auto}.product-list{grid-template-columns:1fr 1fr}}@media(max-width:500px){.cards{grid-template-columns:1fr}.product-list{grid-template-columns:1fr}.top{padding:0 12px}}
</style></head><body>
<div class="top"><img src="logo.png"><div><a href="index.php" target="_blank">View Store</a><a href="?logout=1">Logout</a></div></div>
<div class="wrap">
<div class="tabs"><a class="<?=$tab==='dashboard'?'active':''?>" href="admin.php">Dashboard</a><a class="<?=$tab==='orders'?'active':''?>" href="?tab=orders">Orders</a><a class="<?=$tab==='products'?'active':''?>" href="?tab=products">Products</a></div>

<?php if($tab==='dashboard'): ?>
<h1>SMB HIDES Dashboard</h1><div class="cards">
<div class="card">Products<b><?=$stats['products']?></b></div><div class="card">Total Orders<b><?=$stats['orders']?></b></div><div class="card">New Orders<b><?=$stats['new']?></b></div><div class="card">Sales Value<b><?=money($stats['sales'])?></b></div>
</div>
<h2>Recent Orders</h2>
<div class="tablewrap"><table><tr><th>Order</th><th>Customer</th><th>Phone</th><th>Email</th><th>Total</th><th>Status</th></tr>
<?php foreach(array_slice($orders,0,10) as $o): ?><tr><td><?=e($o['order_no'])?></td><td><?=e($o['customer_name'])?></td><td><?=e($o['phone'])?></td><td><?=e($o['email'])?></td><td><?=money($o['total'])?></td><td><span class="status"><?=e($o['status'])?></span></td></tr><?php endforeach; ?>
</table></div>

<?php elseif($tab==='orders'): ?>
<h1>Customer Orders</h1><p style="color:#888">This is where your team can see the customer's <strong style="color:#dfb96e">name, phone number, email, city, address, products and total</strong>.</p>
<div class="tablewrap"><table><tr><th>Order</th><th>Customer & Contact</th><th>Shipping Address</th><th>Items</th><th>Total</th><th>Status</th></tr>
<?php foreach($orders as $o):
$s=$db->prepare("SELECT * FROM order_items WHERE order_id=?");$s->execute([$o['id']]);$items=$s->fetchAll(PDO::FETCH_ASSOC); ?>
<tr>
<td><b><?=e($o['order_no'])?></b><br><small><?=e($o['created_at'])?></small></td>
<td><b><?=e($o['customer_name'])?></b><br>📞 <?=e($o['phone'])?><br>✉ <?=e($o['email'])?></td>
<td><?=e($o['city'])?><br><?=nl2br(e($o['address']))?></td>
<td><?php foreach($items as $i): ?><?=e($i['product_name'])?> — <?=e($i['size'])?> × <?=e($i['quantity'])?><br><?php endforeach; ?></td>
<td><?=money($o['total'])?><br><small><?=e($o['payment_method'])?></small></td>
<td><form method="post"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?=$o['id']?>"><select name="status" class="field" onchange="this.form.submit()"><?php foreach(['New','Verified','Processing','Shipped','Delivered','Cancelled'] as $st): ?><option <?=$o['status']===$st?'selected':''?>><?=$st?></option><?php endforeach; ?></select></form></td>
</tr>
<?php endforeach; ?></table></div>

<?php elseif($tab==='products'): ?>
<h1>Products</h1>
<div class="formbox">
<h2><?=$edit?'Edit Product':'Add New Product'?></h2>
<form method="post"><input type="hidden" name="action" value="save_product"><input type="hidden" name="id" value="<?=$edit?(int)$edit['id']:0?>">
<div class="grid">
<input class="field" name="name" placeholder="Product name" required value="<?=e($edit['name']??'')?>">
<input class="field" name="category" placeholder="Category (Jackets, Coats...)" value="<?=e($edit['category']??'Jackets')?>">
<input class="field" name="price" type="number" step="0.01" placeholder="Price" required value="<?=e($edit['price']??14999)?>">
<input class="field" name="sale_price" type="number" step="0.01" placeholder="Sale price (optional)" value="<?=e($edit['sale_price']??'')?>">
<input class="field" name="sizes" placeholder="Sizes: S,M,L,XL,XXL" value="<?=e($edit['sizes']??'S,M,L,XL,XXL')?>">
<input class="field" name="stock" type="number" placeholder="Stock" value="<?=e($edit['stock']??10)?>">
<input class="field" name="badge" placeholder="Badge: NEW / SALE / BEST SELLER" value="<?=e($edit['badge']??'')?>">
<input class="field" name="image" placeholder="Product image URL" value="<?=e($edit['image']??'')?>">
<textarea class="field full" name="description" placeholder="Product description"><?=e($edit['description']??'')?></textarea>
<label class="full"><input type="checkbox" name="active" <?=$edit?($edit['active']?'checked':''):'checked'?>> Show product on store</label>
</div><br><button class="btn">SAVE PRODUCT</button> <?php if($edit): ?><a class="btn" href="?tab=products" style="background:#29241e;color:#fff">CANCEL</a><?php endif; ?>
</form></div>
<div class="product-list"><?php foreach($products as $p): ?><div class="p"><img src="<?=e($p['image'])?>"><h3><?=e($p['name'])?></h3><div style="color:#dfb96e"><?=money($p['sale_price']!==null&&$p['sale_price']!==''?$p['sale_price']:$p['price'])?></div><small>Stock: <?=$p['stock']?> · <?=e($p['category'])?></small><div class="actions"><a class="btn" href="?tab=products&edit=<?=$p['id']?>">EDIT</a><a class="btn" style="background:#30251e;color:#e39a7c" href="?action=delete_product&id=<?=$p['id']?>" onclick="return confirm('Delete this product?')">DELETE</a></div></div><?php endforeach; ?></div>
<?php endif; ?>
</div></body></html>
