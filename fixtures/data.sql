-- MySQL dump 10.13  Distrib 8.0.40, for Linux (x86_64)
--
-- Host: db    Database: thedatabase
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `assignment`
--

LOCK TABLES `assignment` WRITE;
/*!40000 ALTER TABLE `assignment` DISABLE KEYS */;
INSERT INTO `assignment` (`id`, `caption`, `description`, `classes`, `classes_regexp`, `school_year`, `public`, `state`, `soft_deadline`, `hard_deadline`, `main_order`, `created_at`, `activated_at`, `owner_id`) VALUES (1,'Maturitní práce','Odevzdejte sem **maturitní práci**.','I4, I2','^I4|I2$',2024,1,'archived',NULL,NULL,1,'2025-05-06 15:11:18','2025-05-06 15:31:09',1),(2,'Test DOCTRINE',NULL,'I4','^I4$',2024,1,'finished',NULL,'2025-05-06 17:12:17',5,'2025-05-06 15:11:18','2025-05-06 15:34:22',8),(3,'Test Symfony','Test symfony','I4','^I4$',2024,1,'ready',NULL,NULL,3,'2025-05-06 15:11:18',NULL,8),(4,'Test','testovací pokus','I4','^I4$',2024,0,'archived',NULL,NULL,1,'2025-05-06 15:11:18','2025-05-06 15:36:02',1),(5,'Test','Toto je **test**.','I4','^I4$',2024,1,'archived',NULL,NULL,1,'2025-05-06 15:11:18','2025-05-06 15:42:14',1),(6,'Zadání 1',NULL,'E1A','^E1A$',2024,0,'archived',NULL,NULL,1,'2025-05-06 15:43:48','2025-05-06 16:23:42',1),(7,'Zadání 2',NULL,'E2A','^E2A$',2024,0,'archived',NULL,NULL,1,'2025-05-06 15:44:06','2025-05-06 18:10:24',1),(8,'Zadání 2','Toto je další testovací zadání','I3','^I3$',2024,0,'active',NULL,NULL,4,'2025-05-06 18:11:14','2025-05-08 22:43:37',1),(9,'Webová aplikace - ročníková práce','Vytvořte webovou aplikaci:\r\n\r\n* musí být napsaná v php\r\n* html a css musí být hezké\r\n* bla bla bla','I3','^I3$',2024,0,'active','2025-05-09 10:00:00','2025-05-09 12:00:00',4,'2025-05-07 12:29:25','2025-05-07 12:30:19',1);
/*!40000 ALTER TABLE `assignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` (`id`, `username`, `name`, `original_role`, `original_student_class`, `effective_role`, `effective_student_class`, `restorable_role`, `last_login_at`) VALUES (1,'sterzik','Marek Sterzik','ROLE_TEACHER',NULL,'ROLE_SUPERADMIN',NULL,NULL,'2025-05-08 22:43:02'),(2,'ucitel.ucitelovic','Učitel Učitelovič','ROLE_TEACHER',NULL,'ROLE_STUDENT','AT1',NULL,NULL),(3,'skolnik.skolnikovic','Školník Školníkovič','ROLE_OTHER',NULL,NULL,NULL,NULL,NULL),(4,'student.studentovic','Student Studentovič','ROLE_STUDENT','I4',NULL,NULL,NULL,NULL),(5,'student.studentovic2','Student Studentovič Nezvěstný','ROLE_STUDENT',NULL,NULL,NULL,NULL,NULL),(6,'student.adminovic','Student Adminovič','ROLE_STUDENT','E4','ROLE_STUDENT',NULL,NULL,NULL),(7,'admin.studentovic','Učitel Studentovič','ROLE_TEACHER',NULL,'ROLE_STUDENT','E1',NULL,NULL),(8,'superadmin.superadminovic','Superadmin Superadminovič','ROLE_TEACHER',NULL,'ROLE_SUPERADMIN',NULL,NULL,NULL),(9,'student','Josef Student','ROLE_STUDENT','I4',NULL,NULL,NULL,'2025-05-05 17:58:48'),(10,'ucitel','Pan Učitel','ROLE_TEACHER',NULL,NULL,NULL,NULL,'2025-05-04 15:28:00'),(11,'profesor','Pan Profesor','ROLE_TEACHER',NULL,NULL,NULL,NULL,'2025-05-03 13:17:11'),(12,'zak','František Žák','ROLE_STUDENT','I4',NULL,NULL,NULL,'2025-05-05 17:58:53'),(13,'zacek','Bedřich Žáček','ROLE_STUDENT','I3',NULL,NULL,NULL,'2025-05-08 22:43:43');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-08 22:06:49
