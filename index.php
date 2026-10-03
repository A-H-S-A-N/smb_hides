<?php
require __DIR__ . '/config.php';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place_order') {
    header('Content-Type: application/json');
    try {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $cart = json_decode($_POST['cart'] ?? '[]', true);

        if (!$name || !$phone || !$email || !$city || !$address || !is_array($cart) || !$cart) {
            throw new Exception('Please complete all checkout details.');
        }

        $ids = [];
        foreach ($cart as $item) {
            if (!empty($item['id'])) $ids[] = (int)$item['id'];
        }
        $ids = array_values(array_unique($ids));
        if (!$ids) throw new Exception('Your cart is empty.');

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT * FROM products WHERE active=1 AND id IN ($placeholders)");
        $stmt->execute($ids);
        $products = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) $products[$p['id']] = $p;

        $total = 0;
        $cleanItems = [];

        foreach ($cart as $item) {
            $id = (int)($item['id'] ?? 0);
            $qty = max(1, min(20, (int)($item['quantity'] ?? 1)));
            $size = trim($item['size'] ?? 'M');
            if (!isset($products[$id])) throw new Exception('A product in your cart is no longer available.');

            $p = $products[$id];
            if ((int)$p['stock'] < $qty) throw new Exception($p['name'] . ' does not have enough stock.');

            $price = $p['sale_price'] !== null && $p['sale_price'] !== '' ? (float)$p['sale_price'] : (float)$p['price'];
            $total += $price * $qty;
            $cleanItems[] = [$p, $size, $qty, $price];
        }

        $orderNo = order_number();
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO orders
            (order_no,customer_name,phone,email,city,address,total,status,payment_method)
            VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$orderNo,$name,$phone,$email,$city,$address,$total,'New','Cash on Delivery']);
        $orderId = (int)$db->lastInsertId();

        $itemStmt = $db->prepare("INSERT INTO order_items
            (order_id,product_id,product_name,size,quantity,price) VALUES (?,?,?,?,?,?)");
        $stockStmt = $db->prepare("UPDATE products SET stock=stock-? WHERE id=?");

        foreach ($cleanItems as [$p,$size,$qty,$price]) {
            $itemStmt->execute([$orderId,$p['id'],$p['name'],$size,$qty,$price]);
            $stockStmt->execute([$qty,$p['id']]);
        }

        $db->commit();

        echo json_encode(['ok'=>true,'order_no'=>$orderNo]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        http_response_code(400);
        echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

$products = $db->query("SELECT * FROM products WHERE active=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$categories = $db->query("SELECT DISTINCT category FROM products WHERE active=1 ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SMB HIDES | Premium Leather Apparel</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#090909;color:#f4efe7;font-family:Arial,sans-serif}a{text-decoration:none;color:inherit}
header{position:sticky;top:0;z-index:50;background:rgba(7,7,7,.96);border-bottom:1px solid #28231d}.nav{max-width:1250px;margin:auto;height:82px;padding:10px 22px;display:flex;align-items:center;gap:35px}.logo{height:60px;width:auto}.links{display:flex;gap:30px;margin:auto}.links a{font-size:13px;color:#ddd}.links a:hover{color:#dfb96e}.cart{background:#17130e;color:#fff;border:1px solid #6a5130;padding:11px 18px;border-radius:30px}
.hero{min-height:650px;display:flex;align-items:center;background:linear-gradient(90deg,rgba(0,0,0,.9) 0%,rgba(0,0,0,.55) 48%,rgba(0,0,0,.25)),url('logo.png') center/500px no-repeat}.hero-inner{max-width:1250px;width:100%;margin:auto;padding:60px 25px}.eyebrow{letter-spacing:5px;color:#d8ad65;font-size:12px}.hero h1{font:56px Georgia,serif;max-width:650px;line-height:1.08;margin:18px 0}.hero p{max-width:540px;color:#bbb;line-height:1.7}.btn{border:0;background:#dfb96e;color:#111;padding:14px 24px;font-weight:bold;cursor:pointer}.section{max-width:1250px;margin:auto;padding:75px 25px}.title{text-align:center;margin-bottom:40px}.title h2{font:40px Georgia,serif;margin:10px}.title p{color:#888}.products{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}.product{border:1px solid #2b2722;background:#12110f}.product img{width:100%;aspect-ratio:4/5;object-fit:cover}.product-body{padding:16px}.badge{display:inline-block;background:#dcb56d;color:#111;font-size:10px;font-weight:bold;padding:5px 8px;margin-bottom:10px}.product h3{font:18px Georgia,serif;margin:4px 0 9px}.desc{font-size:12px;color:#888;line-height:1.5;min-height:36px}.price{color:#e4bc72;font-size:18px;font-weight:bold;margin-top:12px}.old{text-decoration:line-through;color:#777;font-size:12px;margin-left:8px}.add{width:100%;margin-top:14px;padding:12px;border:0;background:#dfb96e;color:#111;font-weight:bold;cursor:pointer}.about{border-top:1px solid #25211d;border-bottom:1px solid #25211d;background:#0e0e0e}.about-inner{max-width:1000px;margin:auto;padding:75px 25px;text-align:center}.about-inner h2{font:42px Georgia,serif}.about-inner p{color:#aaa;line-height:1.8}.contact{display:grid;grid-template-columns:repeat(3,1fr);gap:15px}.contact div{padding:25px;border:1px solid #2b2722;background:#12110f}.contact b{color:#dfb96e;display:block;margin-bottom:8px}.modal,.cartpanel{display:none;position:fixed;z-index:100;inset:0;background:rgba(0,0,0,.82);padding:20px}.box{background:#12110f;border:1px solid #4a3a25;max-width:900px;width:100%;margin:auto;padding:28px;max-height:90vh;overflow:auto;position:relative}.close{position:absolute;right:18px;top:12px;background:none;border:0;color:white;font-size:28px}.detail{display:grid;grid-template-columns:1fr 1fr;gap:30px}.detail img{width:100%;aspect-ratio:4/5;object-fit:cover}.detail h2{font:36px Georgia,serif}.select,.field{width:100%;padding:13px;margin:8px 0;background:#1b1916;border:1px solid #40372e;color:white}.cartbox{max-width:500px;margin-left:auto;height:100%;overflow:auto;background:#111;padding:25px}.cartrow{display:flex;gap:12px;padding:14px 0;border-bottom:1px solid #2b2722}.cartrow img{width:65px;height:80px;object-fit:cover}.qty button{background:#29251f;color:white;border:1px solid #4a4033;width:27px;height:27px}.empty{text-align:center;color:#777;padding:50px 0}.checkout-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.checkout-grid textarea{grid-column:1/-1;min-height:100px}.success{text-align:center;padding:40px;display:none}.success h2{color:#e3bb72;font:35px Georgia,serif}footer{text-align:center;padding:30px;border-top:1px solid #29241e;color:#777}
@media(max-width:900px){.products{grid-template-columns:repeat(2,1fr)}.links{display:none}}@media(max-width:600px){.products{grid-template-columns:1fr 1fr;gap:10px}.hero h1{font-size:42px}.nav{height:68px}.logo{height:50px}.contact{grid-template-columns:1fr}.detail,.checkout-grid{grid-template-columns:1fr}.checkout-grid textarea{grid-column:auto}.section{padding:55px 15px}.product-body{padding:12px}.product h3{font-size:15px}}
</style>
</head>
<body>
<header><div class="nav">
<img src="logo.png" class="logo" alt="SMB HIDES">
<nav class="links"><a href="#home">HOME</a><a href="#shop">SHOP</a><a href="#about">ABOUT US</a><a href="#craft">OUR CRAFT</a><a href="#contact">CONTACT</a></nav>
<button class="cart" onclick="openCart()">🛒 Cart (<span id="count">0</span>)</button>
</div></header>

<section class="hero" id="home"><div class="hero-inner">
<div class="eyebrow">PREMIUM LEATHER APPAREL</div>
<h1>Timeless Style.<br>Lasting Quality.</h1>
<p>At SMB HIDES, we craft premium leather jackets and apparel that combine tradition, durability and modern style. Made in Sialkot, Pakistan.</p>
<button class="btn" onclick="document.getElementById('shop').scrollIntoView()">SHOP NOW →</button>
</div></section>

<section class="section" id="shop"><div class="title"><div class="eyebrow">OUR COLLECTION</div><h2>Featured Collection</h2><p>Premium leather products crafted for every occasion.</p></div>
<div class="products">
<?php foreach($products as $p): $price=$p['sale_price']!==null&&$p['sale_price']!==''?$p['sale_price']:$p['price']; ?>
<div class="product">
<img src="<?=e($p['image'])?>" alt="<?=e($p['name'])?>" loading="lazy">
<div class="product-body">
<?php if($p['badge']): ?><span class="badge"><?=e($p['badge'])?></span><?php endif; ?>
<h3><?=e($p['name'])?></h3><div class="desc"><?=e($p['description'])?></div>
<div class="price"><?=money($price)?><?php if($p['sale_price']!==null&&$p['sale_price']!==''): ?><span class="old"><?=money($p['price'])?></span><?php endif; ?></div>
<button class="add" onclick='openProduct(<?=json_encode($p,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>)'>VIEW & ADD TO CART</button>
</div></div>
<?php endforeach; ?>
</div></section>

<section class="about" id="about"><div class="about-inner"><div class="eyebrow">SMB HIDES</div><h2>Crafted in Sialkot. Made to Last.</h2><p>SMB HIDES is a premium leather manufacturing and apparel brand from Sialkot, Pakistan. We focus on quality materials, careful craftsmanship and timeless design.</p></div></section>

<section class="section" id="craft"><div class="title"><div class="eyebrow">OUR CRAFT</div><h2>Quality Leather. Professional Finish.</h2><p>Designed for customers who value premium leather and lasting construction.</p></div></section>

<section class="section" id="contact"><div class="title"><div class="eyebrow">CONTACT</div><h2>SMB HIDES</h2></div><div class="contact"><div><b>PHONE</b><?=e(STORE_PHONE)?></div><div><b>EMAIL</b><?=e(STORE_EMAIL)?></div><div><b>LOCATION</b>Sialkot, Pakistan</div></div></section>

<footer><b style="color:#dfb96e">SMB HIDES</b><br>Premium Leather • Leather Jackets • Apparel<br>© <?=date('Y')?> SMB HIDES</footer>

<div class="modal" id="productModal"><div class="box"><button class="close" onclick="closeProduct()">×</button><div class="detail"><img id="pimg"><div><div class="eyebrow">SMB HIDES</div><h2 id="pname"></h2><p id="pdesc" style="color:#aaa;line-height:1.7"></p><div class="price" id="pprice"></div><select class="select" id="psize"></select><input class="field" id="pqty" type="number" min="1" value="1"><button class="btn" style="width:100%" onclick="addProduct()">ADD TO CART</button></div></div></div></div>

<div class="modal" id="cartModal"><div class="cartbox"><button class="close" onclick="closeCart()">×</button><h2 style="font:34px Georgia,serif">Your Cart</h2><div id="cartItems"></div><div style="display:flex;justify-content:space-between;padding:20px 0;color:#e3bb72;font-weight:bold">TOTAL <span id="cartTotal">PKR 0</span></div><button class="btn" style="width:100%" onclick="openCheckout()">PROCEED TO CHECKOUT</button></div></div>

<div class="modal" id="checkoutModal"><div class="box"><button class="close" onclick="closeCheckout()">×</button><div id="checkoutView"><h2 style="font:35px Georgia,serif">Checkout</h2><p style="color:#888">Your phone number and email will be visible to the SMB HIDES team with your order.</p>
<form id="checkoutForm"><div class="checkout-grid">
<input class="field" id="name" placeholder="Full Name" required><input class="field" id="phone" placeholder="Phone Number" required>
<input class="field" id="email" type="email" placeholder="Email Address" required><input class="field" id="city" placeholder="City" required>
<textarea class="field" id="address" placeholder="Complete Shipping Address" required></textarea></div>
<div style="padding:18px;background:#181613;border:1px solid #2b2722;margin:15px 0"><b>Payment: Cash on Delivery</b><br><small style="color:#888">You pay when your order is delivered.</small></div>
<div style="display:flex;justify-content:space-between;padding:20px 0;color:#e3bb72;font-weight:bold">ORDER TOTAL <span id="checkoutTotal"></span></div>
<button class="btn" style="width:100%">PLACE ORDER</button></form></div>
<div class="success" id="success"><div style="font-size:55px">✓</div><h2>Order Confirmed</h2><p>Your order number is:</p><strong id="orderno" style="font-size:28px;color:#dfb96e"></strong><p style="color:#888">Our team can now see your name, phone number, email, address and complete order details in the admin panel.</p><button class="btn" onclick="location.reload()">CONTINUE SHOPPING</button></div>
</div></div>

<script>
let products = <?=json_encode($products,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
let cart=JSON.parse(localStorage.getItem('smb_cart')||'[]');
let selected=null;

function save(){localStorage.setItem('smb_cart',JSON.stringify(cart));renderCart();}
function count(){document.getElementById('count').textContent=cart.reduce((a,b)=>a+b.qty,0);}
function openProduct(p){selected=p;document.getElementById('pimg').src=p.image;document.getElementById('pname').textContent=p.name;document.getElementById('pdesc').textContent=p.description;let price=p.sale_price!==null&&p.sale_price!==''?p.sale_price:p.price;document.getElementById('pprice').textContent='PKR '+Number(price).toLocaleString();document.getElementById('psize').innerHTML=(p.sizes||'S,M,L,XL,XXL').split(',').map(s=>`<option>${s.trim()}</option>`).join('');document.getElementById('pqty').value=1;document.getElementById('productModal').style.display='block';}
function closeProduct(){document.getElementById('productModal').style.display='none';}
function addProduct(){let size=document.getElementById('psize').value,qty=Math.max(1,parseInt(document.getElementById('pqty').value)||1);let price=selected.sale_price!==null&&selected.sale_price!==''?Number(selected.sale_price):Number(selected.price);let x=cart.find(i=>i.id==selected.id&&i.size==size);if(x)x.qty+=qty;else cart.push({id:Number(selected.id),size,qty,price});save();closeProduct();openCart();}
function openCart(){document.getElementById('cartModal').style.display='block';renderCart();}
function closeCart(){document.getElementById('cartModal').style.display='none';}
function renderCart(){count();let box=document.getElementById('cartItems');if(!cart.length){box.innerHTML='<div class="empty">Your cart is empty.</div>';document.getElementById('cartTotal').textContent='PKR 0';return;}let total=0;box.innerHTML=cart.map((i,n)=>{let p=products.find(x=>x.id==i.id);if(!p)return '';let price=i.price*i.qty;total+=price;return `<div class="cartrow"><img src="${p.image}"><div style="flex:1"><b>${p.name}</b><div style="color:#888;font-size:12px">Size: ${i.size}<br>PKR ${Number(i.price).toLocaleString()}</div><div class="qty"><button onclick="qty(${n},-1)">−</button> ${i.qty} <button onclick="qty(${n},1)">+</button> <button onclick="removeItem(${n})" style="background:none;border:0;color:#c98b68;margin-left:8px">Remove</button></div></div></div>`}).join('');document.getElementById('cartTotal').textContent='PKR '+total.toLocaleString();}
function qty(n,d){cart[n].qty+=d;if(cart[n].qty<=0)cart.splice(n,1);save();}
function removeItem(n){cart.splice(n,1);save();}
function total(){return cart.reduce((a,i)=>a+i.price*i.qty,0);}
function openCheckout(){if(!cart.length){alert('Your cart is empty.');return;}closeCart();document.getElementById('checkoutTotal').textContent='PKR '+total().toLocaleString();document.getElementById('checkoutModal').style.display='block';}
function closeCheckout(){document.getElementById('checkoutModal').style.display='none';}
document.getElementById('checkoutForm').addEventListener('submit',async e=>{e.preventDefault();let fd=new FormData();fd.append('action','place_order');fd.append('name',document.getElementById('name').value);fd.append('phone',document.getElementById('phone').value);fd.append('email',document.getElementById('email').value);fd.append('city',document.getElementById('city').value);fd.append('address',document.getElementById('address').value);fd.append('cart',JSON.stringify(cart));let r=await fetch('index.php',{method:'POST',body:fd});let data=await r.json();if(!data.ok){alert(data.message||'Unable to place order.');return;}document.getElementById('checkoutView').style.display='none';document.getElementById('success').style.display='block';document.getElementById('orderno').textContent=data.order_no;cart=[];save();});
renderCart();
</script>
</body></html>
