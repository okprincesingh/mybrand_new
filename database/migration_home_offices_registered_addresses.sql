UPDATE home_offices
SET country = 'India',
    address = 'D-226, 10th Avenue, Gaur City 2,\nGr. Noida West, UP - 201301, IN',
    email = 'customersupport@nimishaimpex.com',
    phone = '+91 (971) 700 4615',
    registration_label = 'CIN',
    registration_number = 'U20237UP2025PTC234476',
    image_path = 'assets/imgs/home/office/INDIAN.webp',
    sort_order = 1,
    is_active = 1
WHERE id = 1;

UPDATE home_offices
SET country = 'United States',
    address = '30 N Gould St, Ste R,\nSheridan, WY 82801, USA',
    email = 'customersupport@nimishaimpex.com',
    phone = '+1 (343) 322 5866',
    registration_label = 'EIN',
    registration_number = '41-4152316',
    image_path = 'assets/imgs/home/office/USA-FLAG.webp',
    sort_order = 2,
    is_active = 1
WHERE id = 2;

UPDATE home_offices
SET country = 'United Kingdom',
    address = '128, City Rd, London,\nEC1V 2NX, UNITED KINGDOM',
    email = 'customersupport@nimishaimpex.com',
    phone = '+91 (120) 518 5637',
    registration_label = 'Company No',
    registration_number = '17263045',
    image_path = 'assets/imgs/home/office/Flag-United-Kingdom.webp',
    sort_order = 3,
    is_active = 1
WHERE id = 3;

UPDATE home_offices
SET is_active = 0
WHERE id NOT IN (1, 2, 3);
