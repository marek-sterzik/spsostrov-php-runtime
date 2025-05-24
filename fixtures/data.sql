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
/*!40000 ALTER TABLE `assignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `submission`
--

LOCK TABLES `submission` WRITE;
/*!40000 ALTER TABLE `submission` DISABLE KEYS */;
/*!40000 ALTER TABLE `submission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` (`id`, `username`, `name`, `guessed_surname`, `original_role`, `original_student_class`, `effective_role`, `effective_student_class`, `restorable_role`, `last_login_at`) VALUES (1,'sterzik','Marek Sterzik','Sterzik','ROLE_TEACHER',NULL,'ROLE_SUPERADMIN',NULL,NULL,'2025-05-23 23:30:56'),(2,'ucitel.ucitelovic','Učitel Učitelovič','Učitelovič','ROLE_TEACHER',NULL,'ROLE_STUDENT','AT1',NULL,NULL),(3,'skolnik.skolnikovic','Školník Školníkovič','Školníkovič','ROLE_OTHER',NULL,NULL,NULL,NULL,NULL),(4,'student.studentovic','Student Studentovič','Studentovič','ROLE_STUDENT','I4',NULL,NULL,NULL,NULL),(5,'student.studentovic2','Student Studentovič Nezvěstný','Nezvěstný','ROLE_STUDENT',NULL,NULL,NULL,NULL,NULL),(6,'student.adminovic','Student Adminovič','Adminovič','ROLE_STUDENT','E4','ROLE_STUDENT',NULL,NULL,NULL),(7,'admin.studentovic','Učitel Studentovič','Studentovič','ROLE_TEACHER',NULL,'ROLE_STUDENT','E1',NULL,NULL),(8,'superadmin.superadminovic','Superadmin Superadminovič','Superadminovič','ROLE_TEACHER',NULL,'ROLE_SUPERADMIN',NULL,NULL,NULL),(9,'student','Josef Student','Student','ROLE_STUDENT','I4',NULL,NULL,NULL,'2025-05-14 22:44:46'),(10,'ucitel','Pan Učitel','Učitel','ROLE_TEACHER',NULL,NULL,NULL,NULL,'2025-05-04 15:28:00'),(11,'profesor','Pan Profesor','Profesor','ROLE_TEACHER',NULL,NULL,NULL,NULL,'2025-05-03 13:17:11'),(12,'zak','František Žák','Žák','ROLE_STUDENT','I4',NULL,NULL,NULL,'2025-05-05 17:58:53'),(13,'zacek','Bedřich Žáček','Žáček','ROLE_STUDENT','I3',NULL,NULL,NULL,'2025-05-14 20:48:46');
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

-- Dump completed on 2025-05-24  0:59:48
