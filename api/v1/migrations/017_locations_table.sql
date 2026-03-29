-- api/v1/migrations/017_locations_table.sql
-- Creates mci_locations lookup table and seeds initial city data.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mci_locations` (
  `id`       int unsigned  NOT NULL AUTO_INCREMENT,
  `country`  varchar(100)  NOT NULL,
  `state`    varchar(100)  NOT NULL DEFAULT '',
  `city`     varchar(100)  NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_locations_country_state_city` (`country`, `state`, `city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Soft-reference lookup table for country/state/city. No FK to branches.';

-- Seed: India
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('India','Maharashtra','Mumbai'),
('India','Delhi','Delhi'),
('India','Karnataka','Bangalore'),
('India','Telangana','Hyderabad'),
('India','Tamil Nadu','Chennai'),
('India','West Bengal','Kolkata'),
('India','Maharashtra','Pune'),
('India','Gujarat','Ahmedabad'),
('India','Rajasthan','Jaipur'),
('India','Uttar Pradesh','Lucknow'),
('India','Madhya Pradesh','Bhopal'),
('India','Bihar','Patna'),
('India','Gujarat','Surat'),
('India','Madhya Pradesh','Indore'),
('India','Maharashtra','Nagpur'),
('India','Gujarat','Vadodara'),
('India','Uttar Pradesh','Agra'),
('India','Uttar Pradesh','Varanasi'),
('India','Assam','Guwahati'),
('India','Punjab','Chandigarh');

-- Seed: UK
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('UK','England','London'),
('UK','England','Manchester'),
('UK','England','Birmingham'),
('UK','England','Leeds'),
('UK','England','Sheffield'),
('UK','England','Bristol'),
('UK','England','Liverpool'),
('UK','Scotland','Glasgow'),
('UK','Scotland','Edinburgh'),
('UK','England','Chester'),
('UK','Wales','Cardiff'),
('UK','England','Leicester'),
('UK','England','Nottingham'),
('UK','England','Southampton'),
('UK','England','Oxford'),
('UK','England','Cambridge'),
('UK','England','Bath'),
('UK','England','York'),
('UK','England','Brighton'),
('UK','England','Newcastle');

-- Seed: Australia
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('Australia','New South Wales','Sydney'),
('Australia','Victoria','Melbourne'),
('Australia','Queensland','Brisbane'),
('Australia','Western Australia','Perth'),
('Australia','South Australia','Adelaide'),
('Australia','Queensland','Gold Coast'),
('Australia','Australian Capital Territory','Canberra'),
('Australia','Northern Territory','Darwin'),
('Australia','Tasmania','Hobart'),
('Australia','New South Wales','Newcastle NSW'),
('Australia','New South Wales','Wollongong'),
('Australia','Victoria','Geelong'),
('Australia','Queensland','Townsville'),
('Australia','Queensland','Cairns'),
('Australia','Queensland','Toowoomba'),
('Australia','Victoria','Ballarat'),
('Australia','Victoria','Bendigo'),
('Australia','New South Wales','Albury'),
('Australia','Tasmania','Launceston'),
('Australia','Queensland','Mackay');

-- Seed: Canada
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('Canada','Ontario','Toronto'),
('Canada','British Columbia','Vancouver'),
('Canada','Quebec','Montreal'),
('Canada','Alberta','Calgary'),
('Canada','Ontario','Ottawa'),
('Canada','Alberta','Edmonton'),
('Canada','Manitoba','Winnipeg'),
('Canada','Quebec','Quebec City'),
('Canada','Ontario','Hamilton'),
('Canada','Ontario','Kitchener'),
('Canada','Ontario','London ON'),
('Canada','Nova Scotia','Halifax'),
('Canada','British Columbia','Victoria BC'),
('Canada','Saskatchewan','Saskatoon'),
('Canada','Saskatchewan','Regina'),
('Canada','Ontario','Windsor'),
('Canada','Ontario','Oshawa'),
('Canada','Ontario','Barrie'),
('Canada','British Columbia','Kelowna'),
('Canada','British Columbia','Abbotsford');

-- Seed: USA
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('USA','New York','New York'),
('USA','California','Los Angeles'),
('USA','Illinois','Chicago'),
('USA','Texas','Houston'),
('USA','Arizona','Phoenix'),
('USA','Pennsylvania','Philadelphia'),
('USA','Texas','San Antonio'),
('USA','California','San Diego'),
('USA','Texas','Dallas'),
('USA','Texas','Austin'),
('USA','California','San Jose'),
('USA','Florida','Jacksonville'),
('USA','Texas','Fort Worth'),
('USA','Ohio','Columbus'),
('USA','North Carolina','Charlotte'),
('USA','Indiana','Indianapolis'),
('USA','Washington','Seattle'),
('USA','Colorado','Denver'),
('USA','Massachusetts','Boston'),
('USA','Florida','Miami');

-- Seed: Asia Pacific
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('Singapore','','Singapore'),
('Hong Kong','','Hong Kong'),
('Malaysia','Kuala Lumpur','Kuala Lumpur'),
('Thailand','Bangkok','Bangkok'),
('Indonesia','Jakarta','Jakarta'),
('Philippines','Metro Manila','Manila'),
('Japan','Tokyo','Tokyo'),
('South Korea','Seoul','Seoul'),
('China','Shanghai','Shanghai'),
('China','Beijing','Beijing'),
('UAE','Dubai','Dubai'),
('UAE','Abu Dhabi','Abu Dhabi'),
('Qatar','Doha','Doha'),
('Sri Lanka','Western Province','Colombo'),
('Bangladesh','Dhaka','Dhaka'),
('Nepal','Bagmati','Kathmandu'),
('Pakistan','Sindh','Karachi'),
('Pakistan','Punjab','Lahore'),
('Kenya','Nairobi','Nairobi'),
('South Africa','Western Cape','Cape Town');
