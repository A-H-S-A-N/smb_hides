SMB HIDES E-COMMERCE WEBSITE
===========================

WHAT THIS VERSION DOES
- Customer storefront
- Product categories/products
- Product size selection
- Shopping cart
- Checkout without WhatsApp/social media
- Customer name, phone, email, city and address collected
- Orders saved to an online SQLite database
- Admin login
- Admin dashboard
- Admin can see customer phone + email + address
- Admin can change order status:
  New -> Verified -> Processing -> Shipped -> Delivered / Cancelled
- Admin can add/edit/delete products
- Admin can change price, sale price, category, sizes, image, badge and stock
- Cash on Delivery included

REQUIREMENTS
- PHP 8.0+ hosting
- PDO SQLite enabled
- A domain/hosting account
- Put all files in the same folder
- Make sure the uploads folder is writable (not essential for the URL-based image version)

SETUP
1. Upload all files to your hosting.
2. Put your SMB HIDES logo in the same folder and call it:
   logo.png
3. Open:
   https://YOUR-DOMAIN.com/setup.php
4. Then open:
   https://YOUR-DOMAIN.com/admin.php
5. Default login:
   Username: admin
   Password: SMBHIDES@2026
6. CHANGE THE ADMIN PASSWORD in config.php before going live.
7. Delete or rename setup.php after installation.

IMPORTANT
The admin panel is the part your team uses. When a customer places an order,
the order is inserted into smb_hides.sqlite and becomes visible in Admin -> Orders.
The team can see the customer's name, phone number, email, city, complete address,
ordered products, sizes, quantities, total and payment method.

SECURITY
- Change ADMIN_USERNAME and ADMIN_PASSWORD in config.php.
- Use HTTPS on the real domain.
- Do not share admin credentials.
- Keep PHP and hosting updated.
- For a high-volume store, move from SQLite to MySQL/PostgreSQL and add a proper
  transactional email/SMS/WhatsApp notification service if desired.

PAYMENTS
This starter uses Cash on Delivery. Online payment gateways can be added later.
