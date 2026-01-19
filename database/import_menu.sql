INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Es'), 'Es Teler', 10000, 1),
((SELECT id FROM categories WHERE name = 'Es'), 'Es Teler Durian', 15000, 1),
((SELECT id FROM categories WHERE name = 'Es'), 'Durian Kocok', 10000, 1),
((SELECT id FROM categories WHERE name = 'Es'), 'Alpukat Kocok', 10000, 1),
((SELECT id FROM categories WHERE name = 'Es'), 'Milo', 10000, 1);

INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Boba'), 'Boba Coklat', 10000, 1),
((SELECT id FROM categories WHERE name = 'Boba'), 'Boba Capucino', 10000, 1),
((SELECT id FROM categories WHERE name = 'Boba'), 'Boba Green Tea', 10000, 1);

INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Nutrisari'), 'Nutrisari Mangga', 5000, 1),
((SELECT id FROM categories WHERE name = 'Nutrisari'), 'Nutrisari Anggur', 5000, 1),
((SELECT id FROM categories WHERE name = 'Nutrisari'), 'Nutrisari Cincau', 5000, 1),
((SELECT id FROM categories WHERE name = 'Nutrisari'), 'Nutrisari Milky Oren', 5000, 1),
((SELECT id FROM categories WHERE name = 'Nutrisari'), 'Nutrisari Jeruk Manis', 5000, 1),
((SELECT id FROM categories WHERE name = 'Nutrisari'), 'Nutrisari Jeruk Nipis', 5000, 1),
((SELECT id FROM categories WHERE name = 'Nutrisari'), 'Nutrisari Leci', 5000, 1);

INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Marjan Squash'), 'Marjan Melon', 5000, 1),
((SELECT id FROM categories WHERE name = 'Marjan Squash'), 'Marjan Jeruk', 5000, 1),
((SELECT id FROM categories WHERE name = 'Marjan Squash'), 'Marjan Mangga', 5000, 1),
((SELECT id FROM categories WHERE name = 'Marjan Squash'), 'Marjan Leci', 5000, 1),
((SELECT id FROM categories WHERE name = 'Marjan Squash'), 'Marjan Markisa', 5000, 1),
((SELECT id FROM categories WHERE name = 'Marjan Squash'), 'Marjan Strawberry', 5000, 1),
((SELECT id FROM categories WHERE name = 'Marjan Squash'), 'Marjan Vanila', 5000, 1);

INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Mojito'), 'Mojito Melon', 5000, 1),
((SELECT id FROM categories WHERE name = 'Mojito'), 'Mojito Jeruk', 5000, 1),
((SELECT id FROM categories WHERE name = 'Mojito'), 'Mojito Mangga', 5000, 1),
((SELECT id FROM categories WHERE name = 'Mojito'), 'Mojito Leci', 5000, 1),
((SELECT id FROM categories WHERE name = 'Mojito'), 'Mojito Markisa', 5000, 1),
((SELECT id FROM categories WHERE name = 'Mojito'), 'Mojito Strawberry', 5000, 1),
((SELECT id FROM categories WHERE name = 'Mojito'), 'Mojito Vanila', 5000, 1);

INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Teh'), 'Es Teh Jumbo', 5000, 1),
((SELECT id FROM categories WHERE name = 'Teh'), 'Lemon Tea', 8000, 1),
((SELECT id FROM categories WHERE name = 'Teh'), 'Leci Tea', 8000, 1),
((SELECT id FROM categories WHERE name = 'Teh'), 'Milk Tea', 8000, 1);

INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Kopi'), 'Americano', 5000, 1),
((SELECT id FROM categories WHERE name = 'Kopi'), 'Kopi Vanila', 10000, 1),
((SELECT id FROM categories WHERE name = 'Kopi'), 'Dalgona Kopi', 10000, 1);

INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Soda Gembira'), 'Soda Melon', 7000, 1),
((SELECT id FROM categories WHERE name = 'Soda Gembira'), 'Soda Jeruk', 7000, 1),
((SELECT id FROM categories WHERE name = 'Soda Gembira'), 'Soda Mangga', 7000, 1),
((SELECT id FROM categories WHERE name = 'Soda Gembira'), 'Soda Leci', 7000, 1),
((SELECT id FROM categories WHERE name = 'Soda Gembira'), 'Soda Markisa', 7000, 1),
((SELECT id FROM categories WHERE name = 'Soda Gembira'), 'Soda Vanila', 7000, 1);

INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Oseng Sosis Mix', 15000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Hotdog', 15000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Kebab', 20000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Singkong Retak', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Burger', 15000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Kentang Goreng', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Roti Maryam', 15000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Pring Rolls', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Dumpling Udang', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Ebi Furai', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Mie Jebew', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Sosis', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Nugget', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Corndog Mozza', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Cheese Roll', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Piscok Lumer', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Tela-Tela', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Lumpia Hati Ayam', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Sempol', 10000, 1),
((SELECT id FROM categories WHERE name = 'Cemilan'), 'Roti Panggang', 10000, 1);

INSERT INTO menu_items (category_id, name, price, is_available) VALUES
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Original Isi 4', 12000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Original Isi 6', 15000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Original Isi 10', 24000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Saos Mentai Isi 4', 17000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Saos Mentai Isi 6', 24000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Saos Mentai Isi 10', 32000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Saos Garlic Isi 4', 13000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Saos Garlic Isi 6', 18000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Saos Garlic Isi 10', 28000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Saos Tartar Isi 4', 15000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Saos Tartar Isi 6', 20000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Saos Tartar Isi 10', 30000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Keju Lumer Isi 4', 17000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Keju Lumer Isi 6', 24000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Keju Lumer Isi 10', 32000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Rambutan Isi 4', 10000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Rambutan Isi 6', 14000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Rambutan Isi 10', 23000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Goreng', 12000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Kuah Creamy', 15000, 1),
((SELECT id FROM categories WHERE name = 'Dimsum'), 'Dimsum Wongton Chili Oil', 15000, 1);
