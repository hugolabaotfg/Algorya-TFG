/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.22-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: tienda_db
-- ------------------------------------------------------
-- Server version	10.6.22-MariaDB-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `carritos`
--

DROP TABLE IF EXISTS `carritos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carritos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 1,
  `fecha_agregado` datetime DEFAULT current_timestamp(),
  `avisado` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `carritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `carritos_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carritos`
--

LOCK TABLES `carritos` WRITE;
/*!40000 ALTER TABLE `carritos` DISABLE KEYS */;
INSERT INTO `carritos` VALUES (3,4,5,1,'2026-03-17 13:23:47',1),(4,4,2,1,'2026-03-17 13:23:47',1),(22,2,18,1,'2026-03-25 09:26:11',0),(23,2,17,1,'2026-03-25 09:26:17',0);
/*!40000 ALTER TABLE `carritos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lineas_pedido`
--

DROP TABLE IF EXISTS `lineas_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lineas_pedido` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `lineas_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lineas_pedido_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lineas_pedido`
--

LOCK TABLES `lineas_pedido` WRITE;
/*!40000 ALTER TABLE `lineas_pedido` DISABLE KEYS */;
INSERT INTO `lineas_pedido` VALUES (1,1,20,1,9.85),(2,2,15,1,599.00),(3,3,21,1,7.95),(4,4,21,1,7.95),(5,5,21,1,7.95),(6,5,22,1,12.99),(7,6,10,2,10.99),(8,7,22,1,399.99),(9,8,22,1,399.99);
/*!40000 ALTER TABLE `lineas_pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT 'Stripe (Sandbox)',
  `fecha` datetime DEFAULT current_timestamp(),
  `stripe_session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,2,9.85,'Stripe (Sandbox)','2026-03-19 11:26:01',NULL),(2,2,599.00,'Stripe (Sandbox)','2026-03-19 12:17:54',NULL),(3,2,7.95,'Stripe (Sandbox)','2026-03-20 07:57:38',NULL),(4,2,7.95,'Stripe (Sandbox)','2026-03-20 11:23:32',NULL),(5,2,20.94,'Stripe (Sandbox)','2026-03-20 11:42:45',NULL),(6,2,21.98,'Stripe (Sandbox)','2026-03-20 14:55:42',NULL),(7,2,399.99,'Stripe (Sandbox)','2026-03-25 08:23:19',NULL),(8,6,399.99,'Stripe (Sandbox)','2026-03-25 10:35:45','cs_test_a1QOfzvbh5h5PwX1oq9hfpQoUOzXDvYINouPhZa25M7IstHbg4sCSrdUS1');
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_id` int(11) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `imagen` varchar(255) DEFAULT 'default.jpg',
  `destacado` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_id` (`api_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (2,NULL,'Zapatillas Cyberpunk','Edición limitada neón. ',100.00,34,'producto_697a620af1b0c_cyberpunk.png',0),(3,NULL,'Fjallraven - Foldsack No. 1 Backpack, Fits 15 Laptops','Your perfect pack for everyday use and walks in the forest. Stash your laptop (up to 15 inches) in the padded sleeve, your everyday...',109.95,27,'api_1.jpg',0),(4,NULL,'Mens Casual Premium Slim Fit T-Shirts ','Slim-fitting style, contrast raglan long sleeve, three-button henley placket, light weight & soft fabric for breathable and comfortable wearing. And S...',22.30,46,'api_2.jpg',0),(5,NULL,'Mens Cotton Jacket','great outerwear jackets for Spring/Autumn/Winter, suitable for many occasions, such as working, hiking, camping, mountain/rock climbing, cycling, trav...',55.99,44,'api_3.jpg',0),(6,NULL,'Mens Casual Slim Fit','The color could be slightly different between on the screen and in practice. / Please note that body builds vary by person, therefore, detailed size i...',15.99,31,'api_4.jpg',0),(7,NULL,'John Hardy Women\'s Legends Naga Gold & Silver Dragon Station Chain Bracelet','From our Legends Collection, the Naga was inspired by the mythical water dragon that protects the ocean\'s pearl. Wear facing inward to be bestowed wit...',695.00,14,'api_5.jpg',0),(8,NULL,'Solid Gold Petite Micropave ','Satisfaction Guaranteed. Return or exchange any order within 30 days.Designed and sold by Hafeez Center in the United States. Satisfaction Guaranteed....',168.00,20,'api_6.jpg',0),(9,NULL,'White Gold Plated Princess','Classic Created Wedding Engagement Solitaire Diamond Promise Ring for Her. Gifts to spoil your love more for Engagement, Wedding, Anniversary, Valentine\'s Day......',9.99,19,'api_7.jpg',0),(10,NULL,'Pierced Owl Rose Gold Plated Stainless Steel Double','Rose Gold Plated Double Flared Tunnel Plug Earrings. Made of 316L Stainless Steel...',10.99,30,'api_8.jpg',0),(11,NULL,'WD 2TB Elements Portable External Hard Drive - USB 3.0 ','USB 3.0 and USB 2.0 Compatibility Fast data transfers Improve PC Performance High Capacity; Compatibility Formatted NTFS for Windows 10, Windows 8.1, Windows 7; Reformatting may be required for other ...',64.00,45,'api_9.jpg',0),(12,NULL,'SanDisk SSD PLUS 1TB Internal SSD - SATA III 6 Gb/s','Easy upgrade for faster boot up, shutdown, application load and response (As compared to 5400 RPM SATA 2.5” hard drive; Based on published specifications and internal benchmarking tests using PCMark...',109.00,13,'api_10.jpg',0),(13,NULL,'Silicon Power 256GB SSD 3D NAND A55 SLC Cache Performance Boost SATA III 2.5','3D NAND flash are applied to deliver high transfer speeds Remarkable transfer speeds that enable faster bootup and improved overall system performance. The advanced SLC Cache Technology allows perform...',109.00,14,'api_11.jpg',0),(14,NULL,'WD 4TB Gaming Drive Works with Playstation 4 Portable External Hard Drive','Expand your PS4 gaming experience, Play anywhere Fast and easy, setup Sleek design with high capacity, 3-year manufacturer\'s limited warranty...',114.00,35,'api_12.jpg',0),(15,NULL,'Acer SB220Q bi 21.5 inches Full HD (1920 x 1080) IPS Ultra-Thin','21. 5 inches Full HD (1920 x 1080) widescreen IPS display And Radeon free Sync technology. No compatibility for VESA Mount Refresh Rate: 75Hz - Using HDMI port Zero-frame design | ultra-thin | 4ms res...',599.00,45,'api_13.jpg',0),(16,NULL,'Samsung 49-Inch CHG90 144Hz Curved Gaming Monitor (LC49HG90DMNXZA) – Super Ultrawide Screen QLED ','49 INCH SUPER ULTRAWIDE 32:9 CURVED GAMING MONITOR with dual 27 inch screen side by side QUANTUM DOT (QLED) TECHNOLOGY, HDR support and factory calibration provides stunningly realistic and accurate c...',999.99,42,'api_14.jpg',0),(17,NULL,'BIYLACLESEN Women\'s 3-in-1 Snowboard Jacket Winter Coats','Note:The Jackets is US standard size, Please choose size as your usual wear Material: 100% Polyester; Detachable Liner Fabric: Warm Fleece. Detachable Functional Liner: Skin Friendly, Lightweigt and W...',56.99,26,'api_15.jpg',0),(18,NULL,'Lock and Love Women\'s Removable Hooded Faux Leather Moto Biker Jacket','100% POLYURETHANE(shell) 100% POLYESTER(lining) 75% POLYESTER 25% COTTON (SWEATER), Faux leather material for style and comfort / 2 pockets of front, 2-For-One Hooded denim style faux leather jacket, ...',29.97,13,'api_16.jpg',0),(19,NULL,'Rain Jacket Women Windbreaker Striped Climbing Raincoats','Lightweight perfet for trip or casual wear---Long sleeve with hooded, adjustable drawstring waist design. Button and zipper front closure raincoat, fully stripes Lined and The Raincoat has 2 side pock...',39.99,33,'api_17.jpg',0),(20,NULL,'MBJ Women\'s Solid Short Sleeve Boat Neck V ','95% RAYON 5% SPANDEX, Made in USA or Imported, Do Not Bleach, Lightweight fabric with great stretch for comfort, Ribbed on sleeves and neckline / Double stitching on bottom hem...',9.85,44,'api_18.jpg',0),(21,NULL,'Opna Women\'s Short Sleeve Moisture','100% Polyester, Machine wash, 100% cationic polyester interlock, Machine Wash & Pre Shrunk for a Great Fit, Lightweight, roomy and highly breathable with moisture wicking fabric which helps to keep mo...',7.95,44,'api_19.jpg',0),(22,NULL,'Play Station 5','95%Cotton,5%Spandex, Features: Casual, Short Sleeve, Letter Print,V-Neck,Fashion Tees, The fabric is soft and has some stretch., Occasion: Casual/Office/Beach/School/Home/Street. Season: Spring,Summer...',399.99,2,'prod_22_1774115849.jpg',0),(23,NULL,'XBox One','',777.01,4,'prod_23_1774115747.jpg',0);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','cliente') DEFAULT 'cliente',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `verificado` tinyint(1) DEFAULT 0,
  `token_verificacion` varchar(100) DEFAULT NULL,
  `notif_promos` tinyint(1) DEFAULT 1,
  `notif_pedidos` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Hugo Admin','admin@dropshiphgl.local','$2y$10$yO02KWeG8l97sNqP8YF4/OqV6Y.Lw9pQ4gU8sL9R7o.uY1K7n4S2O','admin','2026-01-14 20:30:30',1,NULL,1,1),(2,'Hugo Prueba Marzo','hlabgon3001@g.educaand.es','$2y$10$xHnsUgQ/Ed20w0w5GZ5EaeqTN5mgoARcXlrS53LVzNpq/hS/bCMee','cliente','2026-03-18 09:58:53',1,NULL,0,0),(3,'El Jefe','jefe@dropshiphgl.local','$2y$10$8AQNdto.gxOxgC2zzD108efSIIJaqxJ.kBa1taPmnzYK7ATfM8gAi','admin','2026-03-18 10:39:42',1,NULL,1,1),(4,'Hugo Labao','hugo.labao04@gmail.com','$2y$10$D6fPOGVsajDE8s/8C87ciexRNv7ysbeiyX5A7w5CymRnaCe3yozeu','cliente','2026-03-18 12:54:49',1,NULL,1,1),(5,'ddddddddddddddddsadsadddddddddddddddddddsaddasdddd','peperamon@dsd.cpm','$2y$10$g4ClgVsiieo./WRX2YtuuOEiVo0OvaZbY3d9eAOEtfwlc.QhYVfMW','cliente','2026-03-21 16:18:57',0,'0dcdbba16b2560f3ef2b50561d9d638a',1,1),(6,'Hugo Prueba 2','hlabaogonzalez18@gmail.com','$2y$10$ut5r7gp3L06JyY3hD0XTzemOEF/6e0/ftlNpcMakpW4v3dUY/ogb6','cliente','2026-03-25 09:51:50',1,NULL,1,1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-25 10:37:18
