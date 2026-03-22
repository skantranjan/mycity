-- =============================================================================
-- Migration / Seed 011: Business listing seed data — My City Info
-- Run after: seed_categories.sql, seed_tags.sql, and 010_system_error_log.sql
--
-- All category and tag references use slug-based subqueries to be safe against
-- auto-increment IDs assigned during seed_categories.sql / seed_tags.sql runs.
--
-- 12 businesses across real categories from seed_categories.sql:
--   • Spark Electricals        → Home Services > Electrical Services
--   • CleanNest Home Services  → Home Services > Cleaning Services
--   • QuickCare Dental Clinic  → Health & Medical > Dentists
--   • LifeStep Physiotherapy   → Health & Medical > Physiotherapy
--   • Glamour Studio           → Beauty & Personal Care > Salons
--   • Urban Spa House          → Beauty & Personal Care > Spas
--   • Naveen Famous Veg        → Food & Restaurants > Restaurants
--   • Cafe Aroma               → Food & Restaurants > Cafes
--   • Chester Gym & Fitness    → Fitness & Sports > Gyms
--   • Sunrise Hotel Rooms      → Travel & Hospitality > Hotels
--   • Property 852             → Real Estate > Commercial Spaces
--   • SwiftMove Packers        → Logistics & Delivery > Packers & Movers
--
-- Added by seeded super-admin: e0000000-0000-4000-8000-000000000010
-- All status = live  (cp_admin submissions go live immediately)
-- Idempotent: INSERT IGNORE throughout — safe to re-run.
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;


-- =============================================================================
-- 1) BUSINESS GROUPS
-- parent_category_id resolved via slug subquery from seed_categories.sql
-- =============================================================================
INSERT IGNORE INTO `mci_business_groups`
  (`id`, `name`, `slug`, `tagline`, `description`, `established_year`,
   `website_url`, `email`,
   `parent_category_id`, `price_range`, `status`, `added_by_role`, `added_by_user_id`,
   `page_title`, `meta_keywords`, `meta_description`,
   `created_at`, `created_by_user_id`)
VALUES

-- ── BIZ 01: Spark Electricals ─────────────────────────────────────────────────
('b1000000-0000-4000-8000-000000000001',
 'Spark Electricals',
 'spark-electricals',
 'Licensed electricians in Bangalore — residential, commercial and 24/7 emergency.',
 'Spark Electricals is a fully licensed and insured electrical contracting company serving Bangalore since 2009. We handle residential wiring, switchboard upgrades, safety inspections, commercial fit-outs and 24/7 emergency callouts. Our team of certified electricians delivers fast, reliable and competitively priced solutions across the city.',
 2009, 'https://sparkelectricals.example.in', 'service@sparkelectricals.example.in',
 (SELECT id FROM mci_categories WHERE slug = 'home-services' LIMIT 1),
 'free', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'Spark Electricals — Licensed Electricians Bangalore | 24/7 Emergency',
 'electrician Bangalore, electrical wiring, emergency electrician, switchboard upgrade, licensed electrician, home electrical service',
 'Licensed electricians in Bangalore for residential, commercial and emergency electrical work. Fast response, competitive rates.',
 '2026-01-10 09:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 02: CleanNest Home Services ──────────────────────────────────────────
('b1000000-0000-4000-8000-000000000002',
 'CleanNest Home Services',
 'cleannest-home-services',
 'Professional home and office deep cleaning across Pune.',
 'CleanNest Home Services provides professional, eco-friendly cleaning solutions for homes, offices, and commercial spaces across Pune. Our trained staff uses hospital-grade sanitisers and HEPA-filter equipment for deep cleaning, sofa and carpet cleaning, move-in/move-out cleaning, and post-renovation cleaning. All staff are verified and insured.',
 2017, 'https://cleannest.example.in', 'book@cleannest.example.in',
 (SELECT id FROM mci_categories WHERE slug = 'home-services' LIMIT 1),
 'moderate', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'CleanNest Home Services — Deep Cleaning Specialists Pune',
 'home cleaning Pune, deep cleaning, office cleaning, sofa cleaning, move-in cleaning, post renovation cleaning',
 'Professional home and office deep cleaning in Pune. Trained staff, eco-friendly products, and guaranteed results.',
 '2026-01-11 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 03: QuickCare Dental Clinic ──────────────────────────────────────────
('b1000000-0000-4000-8000-000000000003',
 'QuickCare Dental Clinic',
 'quickcare-dental-clinic',
 'Comprehensive dental care for the whole family in Mumbai.',
 'QuickCare Dental Clinic is a modern, fully equipped dental practice in Bandra, Mumbai. We offer a complete range of dental services for adults and children — routine checkups, fillings, root canals, braces, teeth whitening, and dental implants. Our experienced dentists provide gentle, patient-centred care with same-day emergency appointments available.',
 2013, 'https://quickcaredental.example.in', 'appointments@quickcaredental.example.in',
 (SELECT id FROM mci_categories WHERE slug = 'health-medical' LIMIT 1),
 'pricey', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'QuickCare Dental Clinic — Family Dentist Mumbai Bandra',
 'dentist Mumbai, dental clinic Bandra, teeth whitening, braces, dental implants, root canal, family dentist',
 'Comprehensive dental care for the whole family in Mumbai. Checkups, orthodontics, whitening, implants — book today.',
 '2026-01-12 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 04: LifeStep Physiotherapy ───────────────────────────────────────────
('b1000000-0000-4000-8000-000000000004',
 'LifeStep Physiotherapy',
 'lifestep-physiotherapy',
 'Expert physiotherapy and sports injury rehabilitation in Hyderabad.',
 'LifeStep Physiotherapy is a specialist physiotherapy and rehabilitation clinic in Hyderabad. Our experienced physiotherapists offer personalised treatment plans for sports injuries, back and neck pain, post-surgical rehabilitation, dry needling, and neurological physiotherapy. Home visit services are available across Hyderabad.',
 2014, 'https://lifestepphysio.example.in', 'clinic@lifestepphysio.example.in',
 (SELECT id FROM mci_categories WHERE slug = 'health-medical' LIMIT 1),
 'pricey', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'LifeStep Physiotherapy — Sports Injury & Rehab Hyderabad',
 'physiotherapy Hyderabad, sports injury rehab, back pain treatment, dry needling, post surgical rehab, home visit physio',
 'Expert physiotherapy and sports injury rehabilitation in Hyderabad. Home visits available. Book your assessment today.',
 '2026-01-13 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 05: Glamour Studio ───────────────────────────────────────────────────
('b1000000-0000-4000-8000-000000000005',
 'Glamour Studio',
 'glamour-studio',
 'Premium unisex hair and beauty salon in the heart of Delhi.',
 'Glamour Studio is a premium unisex hair and beauty salon in Connaught Place, Delhi. Our team of expert stylists and beauty therapists offer haircuts, colouring, keratin treatments, facials, threading, waxing, nail art, and bridal packages. We use only professional-grade international products for outstanding results.',
 2015, 'https://glamourstudio.example.in', 'hello@glamourstudio.example.in',
 (SELECT id FROM mci_categories WHERE slug = 'beauty-personal-care' LIMIT 1),
 'pricey', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'Glamour Studio — Premium Unisex Salon Delhi Connaught Place',
 'salon Delhi, unisex salon, hair colouring, keratin treatment, bridal makeup, nail art, beauty parlour Connaught Place',
 'Premium unisex salon in Delhi offering haircuts, colouring, facials, bridal packages and more. Book your appointment today.',
 '2026-01-14 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 06: Urban Spa House ───────────────────────────────────────────────────
('b1000000-0000-4000-8000-000000000006',
 'Urban Spa House',
 'urban-spa-house',
 'Jaipur\'s luxury wellness destination for massage and beauty treatments.',
 'Urban Spa House is Jaipur\'s premier luxury wellness and beauty spa. Our expert therapists offer Swedish, deep-tissue and hot-stone massages, Ayurvedic treatments, hydrating facials, body scrubs, waxing, and couple\'s packages. All treatment rooms are private and fully air-conditioned. Corporate wellness programmes available.',
 2016, 'https://urbanspahouse.example.in', 'bookings@urbanspahouse.example.in',
 (SELECT id FROM mci_categories WHERE slug = 'beauty-personal-care' LIMIT 1),
 'pricey', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'Urban Spa House — Luxury Massage & Spa Jaipur',
 'spa Jaipur, massage Jaipur, Ayurvedic massage, couple spa, body scrub, luxury spa, facial Jaipur',
 'Luxury spa and wellness in Jaipur. Expert massages, Ayurvedic treatments, facials and couple\'s packages.',
 '2026-01-15 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 07: Naveen Famous Veg Restaurant ─────────────────────────────────────
('b1000000-0000-4000-8000-000000000007',
 'Naveen Famous Veg Restaurant',
 'naveen-famous-veg-restaurant',
 'Bhopal\'s most loved pure vegetarian restaurant since 1998.',
 'Naveen Famous Veg Restaurant has been serving authentic vegetarian cuisine to the people of Bhopal for over 25 years. Known for generous thali meals, fresh dals, paneer dishes and home-style cooking, we also offer full catering services for weddings, corporate events and family functions. Pure veg kitchen, no eggs. Family seating for up to 200 guests.',
 1998, 'https://naveenveg.example.in', 'naveen.restaurant@example.in',
 (SELECT id FROM mci_categories WHERE slug = 'food-restaurants' LIMIT 1),
 'free', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'Naveen Famous Veg Restaurant — Pure Veg Thali & Catering Bhopal',
 'pure veg restaurant Bhopal, vegetarian thali, Indian vegetarian food, catering Bhopal, family restaurant, veg food Bhopal',
 'Bhopal\'s favourite vegetarian restaurant since 1998. Generous thali meals, home-style cooking and event catering.',
 '2026-01-16 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 08: Cafe Aroma ───────────────────────────────────────────────────────
('b1000000-0000-4000-8000-000000000008',
 'Cafe Aroma',
 'cafe-aroma',
 'Delhi\'s favourite specialty coffee and all-day breakfast cafe.',
 'Cafe Aroma is a warm, welcoming specialty coffee cafe in Connaught Place, Delhi. We serve single-origin espresso drinks, cold brews, filter coffee and a full all-day breakfast and brunch menu. Our baristas are trained to SCA standards and we source beans directly from Coorg, Chikmagalur and Araku Valley farms. Free WiFi, vegan options available.',
 2017, 'https://cafearoma.example.in', 'hello@cafearoma.example.in',
 (SELECT id FROM mci_categories WHERE slug = 'food-restaurants' LIMIT 1),
 'free', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'Cafe Aroma — Specialty Coffee & All-Day Breakfast Delhi',
 'specialty coffee Delhi, cafe Delhi, single origin coffee, all day breakfast, brunch Delhi, cold brew, cafe Connaught Place',
 'Delhi\'s favourite specialty coffee cafe. Single-origin espresso, cold brew and hearty all-day breakfast near Connaught Place.',
 '2026-01-17 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 09: Chester Gym & Fitness ────────────────────────────────────────────
('b1000000-0000-4000-8000-000000000009',
 'Chester Gym & Fitness',
 'chester-gym-fitness',
 'Chester\'s premier gym with personal training and group fitness classes.',
 'Chester Gym & Fitness is the city\'s leading fitness destination, offering state-of-the-art equipment, certified personal trainers and a full timetable of group classes including HIIT, yoga, spin and Zumba. We cater to all fitness levels from beginners to competitive athletes. Flexible monthly and annual membership plans, no joining fee on annual.',
 2011, 'https://chestergym.example.co.uk', 'info@chestergym.example.co.uk',
 (SELECT id FROM mci_categories WHERE slug = 'fitness-sports' LIMIT 1),
 'moderate', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'Chester Gym & Fitness — Personal Training & Group Classes Chester UK',
 'gym Chester, personal trainer Chester, group fitness classes, HIIT, yoga Chester, spin class, fitness centre Chester',
 'Chester\'s premier gym. Personal training, group fitness classes and flexible memberships for all levels.',
 '2026-01-18 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 10: Sunrise Hotel Rooms ──────────────────────────────────────────────
('b1000000-0000-4000-8000-000000000010',
 'Sunrise Hotel Rooms',
 'sunrise-hotel-rooms',
 'Comfortable and affordable accommodation in the heart of Patna.',
 'Sunrise Hotel Rooms offers clean, comfortable and affordable accommodation in a central Patna location. Ideal for business travellers, tourists and long-stay guests. Amenities include free Wi-Fi, 24-hour reception, room service, an in-house restaurant serving Indian and continental cuisine, conference facilities and free parking.',
 2005, 'https://sunrisehotel.example.in', 'bookings@sunrisehotel.example.in',
 (SELECT id FROM mci_categories WHERE slug = 'travel-hospitality' LIMIT 1),
 'free', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'Sunrise Hotel Rooms — Affordable Hotel in Patna | Book Direct',
 'hotel Patna, affordable hotel Patna, budget hotel, rooms Patna, hotel near railway station, conference hotel Patna',
 'Comfortable and affordable hotel in central Patna. Free Wi-Fi, parking, room service and conference facilities.',
 '2026-01-19 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 11: Property 852 ─────────────────────────────────────────────────────
('b1000000-0000-4000-8000-000000000011',
 'Property 852',
 'property-852',
 'Premium commercial and residential real estate advisory in Hong Kong.',
 'Property 852 is a leading commercial and residential property agency based in Hong Kong Central, specialising in waterfront offices, premium leasing solutions and real estate advisory. With over a decade of market expertise, our bilingual team connects businesses and individuals with ideal spaces — from Grade A offices to luxury waterfront apartments.',
 2012, 'https://property852.example.hk', 'enquiries@property852.example.hk',
 (SELECT id FROM mci_categories WHERE slug = 'real-estate' LIMIT 1),
 'ultra', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'Property 852 — Commercial & Residential Real Estate Hong Kong',
 'real estate Hong Kong, commercial property, office leasing, luxury apartments, property advisory, waterfront offices HK',
 'Premium commercial and residential real estate advisory in Hong Kong. Office leasing, luxury apartments and property advisory.',
 '2026-01-20 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- ── BIZ 12: SwiftMove Packers & Movers ───────────────────────────────────────
('b1000000-0000-4000-8000-000000000012',
 'SwiftMove Packers & Movers',
 'swiftmove-packers-movers',
 'Safe, affordable relocation and logistics across India.',
 'SwiftMove Packers & Movers is a trusted pan-India relocation and logistics company headquartered in Chennai. We offer home and office shifting, vehicle transportation, international relocation, warehouse storage, and last-mile delivery services. All moves are GPS-tracked and fully insured. Same-day booking available for local moves.',
 2010, 'https://swiftmove.example.in', 'move@swiftmove.example.in',
 (SELECT id FROM mci_categories WHERE slug = 'logistics-delivery' LIMIT 1),
 'moderate', 'live', 'cp_admin', 'e0000000-0000-4000-8000-000000000010',
 'SwiftMove Packers & Movers — Relocation & Logistics Across India',
 'packers and movers Chennai, home shifting, office relocation, vehicle transport, international moving, logistics India',
 'Trusted packers and movers across India. Home shifting, office relocation, vehicle transport and storage — GPS-tracked.',
 '2026-01-21 10:00:00', 'e0000000-0000-4000-8000-000000000010');


-- =============================================================================
-- 2) BUSINESS BRANCHES
-- =============================================================================
INSERT IGNORE INTO `mci_business_branches`
  (`id`, `business_group_id`, `slug`, `branch_label`,
   `address_line1`, `address_line2`, `city`, `state`, `country`, `pincode`,
   `latitude`, `longitude`,
   `phone_primary`, `phone_secondary`, `whatsapp_number`,
   `is_primary`, `status`, `created_at`, `created_by_user_id`)
VALUES
-- BIZ 01: Spark Electricals — Bangalore
('c1000000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000001',
 'spark-electricals-bangalore', NULL,
 '18 HAL 2nd Stage', '100 Feet Road', 'Bangalore', 'Karnataka', 'India', '560008',
 12.9745, 77.7462, '+91 80 4112 9900', '+91 98450 11223', '+91 98450 11223',
 1, 'active', '2026-01-10 09:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 02: CleanNest — Pune
('c1000000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000002',
 'cleannest-home-services-pune', NULL,
 '45 FC Road', 'Shivajinagar', 'Pune', 'Maharashtra', 'India', '411005',
 18.5204, 73.8567, '+91 20 4811 2200', NULL, '+91 98220 44455',
 1, 'active', '2026-01-11 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 03: QuickCare Dental — Mumbai Bandra
('c1000000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000003',
 'quickcare-dental-mumbai-bandra', NULL,
 '201 Hill Road', 'Near St Andrews Church, Bandra West', 'Mumbai', 'Maharashtra', 'India', '400050',
 19.0596, 72.8295, '+91 22 2640 5555', '+91 98200 66777', '+91 98200 66777',
 1, 'active', '2026-01-12 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 04: LifeStep Physiotherapy — Hyderabad
('c1000000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000004',
 'lifestep-physiotherapy-hyderabad-banjara-hills', NULL,
 '12-2-831 Banjara Hills', 'Road No. 2', 'Hyderabad', 'Telangana', 'India', '500034',
 17.4100, 78.4500, '+91 40 4545 6767', NULL, '+91 99890 12345',
 1, 'active', '2026-01-13 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 05: Glamour Studio — Delhi
('c1000000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000005',
 'glamour-studio-delhi-connaught-place', NULL,
 '14 Block N, Inner Circle', 'Connaught Place', 'Delhi', 'Delhi', 'India', '110001',
 28.6315, 77.2167, '+91 11 4155 8800', NULL, '+91 99990 22233',
 1, 'active', '2026-01-14 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 06: Urban Spa House — Jaipur
('c1000000-0000-4000-8000-000000000006','b1000000-0000-4000-8000-000000000006',
 'urban-spa-house-jaipur-c-scheme', NULL,
 '12 Sardar Patel Marg', 'C-Scheme', 'Jaipur', 'Rajasthan', 'India', '302001',
 26.9124, 75.7873, '+91 141 400 5678', NULL, '+91 96729 88888',
 1, 'active', '2026-01-15 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 07: Naveen Veg Restaurant — Bhopal
('c1000000-0000-4000-8000-000000000007','b1000000-0000-4000-8000-000000000007',
 'naveen-famous-veg-restaurant-bhopal-mp-nagar', NULL,
 '42 Zone-II, MP Nagar', NULL, 'Bhopal', 'Madhya Pradesh', 'India', '462011',
 23.2390, 77.4326, '+91 755 246 8888', '+91 98261 00001', '+91 98261 00001',
 1, 'active', '2026-01-16 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 08: Cafe Aroma — Delhi
('c1000000-0000-4000-8000-000000000008','b1000000-0000-4000-8000-000000000008',
 'cafe-aroma-delhi-connaught-place', NULL,
 '7 Block A, Connaught Place', NULL, 'Delhi', 'Delhi', 'India', '110001',
 28.6330, 77.2195, '+91 11 4155 7890', NULL, '+91 99999 12345',
 1, 'active', '2026-01-17 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 09: Chester Gym — Chester UK
('c1000000-0000-4000-8000-000000000009','b1000000-0000-4000-8000-000000000009',
 'chester-gym-fitness-city-centre', NULL,
 '78 Northgate Street', NULL, 'Chester', 'Cheshire', 'United Kingdom', 'CH1 2HQ',
 53.1962, -2.8875, '+44 1244 777 888', NULL, '+44 7711 223 344',
 1, 'active', '2026-01-18 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 10: Sunrise Hotel — Patna
('c1000000-0000-4000-8000-000000000010','b1000000-0000-4000-8000-000000000010',
 'sunrise-hotel-rooms-patna', NULL,
 'Exhibition Road, Frazer Road', NULL, 'Patna', 'Bihar', 'India', '800001',
 25.5941, 85.1376, '+91 612 222 4444', '+91 612 222 4445', '+91 94313 00002',
 1, 'active', '2026-01-19 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 11: Property 852 — Hong Kong Central
('c1000000-0000-4000-8000-000000000011','b1000000-0000-4000-8000-000000000011',
 'property-852-hong-kong-central', NULL,
 'Suite 2201, The Center, 99 Queen\'s Road Central', NULL,
 'Hong Kong', 'Hong Kong Island', 'Hong Kong', NULL,
 22.2798, 114.1588, '+852 2100 8520', NULL, '+852 9100 8520',
 1, 'active', '2026-01-20 10:00:00', 'e0000000-0000-4000-8000-000000000010'),

-- BIZ 12: SwiftMove — Chennai
('c1000000-0000-4000-8000-000000000012','b1000000-0000-4000-8000-000000000012',
 'swiftmove-packers-movers-chennai', NULL,
 '22 Anna Salai', 'Teynampet', 'Chennai', 'Tamil Nadu', 'India', '600018',
 13.0418, 80.2341, '+91 44 4210 3300', '+91 98400 55566', '+91 98400 55566',
 1, 'active', '2026-01-21 10:00:00', 'e0000000-0000-4000-8000-000000000010');


-- =============================================================================
-- 3) BRANCH HOURS
-- =============================================================================
INSERT IGNORE INTO `mci_business_branch_hours`
  (`id`, `branch_id`, `day_of_week`, `opens_at`, `closes_at`, `is_closed`, `created_at`)
VALUES
-- BIZ 01: Spark Electricals — Mon–Sat 8–19, Sun emergency open 24h
('h0100000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000001','monday',   '08:00','19:00',0,'2026-01-10 09:00:00'),
('h0100000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000001','tuesday',  '08:00','19:00',0,'2026-01-10 09:00:00'),
('h0100000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000001','wednesday','08:00','19:00',0,'2026-01-10 09:00:00'),
('h0100000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000001','thursday', '08:00','19:00',0,'2026-01-10 09:00:00'),
('h0100000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000001','friday',   '08:00','19:00',0,'2026-01-10 09:00:00'),
('h0100000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000001','saturday', '08:00','16:00',0,'2026-01-10 09:00:00'),
('h0100000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000001','sunday',   '00:00','23:59',0,'2026-01-10 09:00:00'),
-- BIZ 02: CleanNest — Mon–Sat 7–19, Sun closed
('h0200000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000002','monday',   '07:00','19:00',0,'2026-01-11 10:00:00'),
('h0200000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000002','tuesday',  '07:00','19:00',0,'2026-01-11 10:00:00'),
('h0200000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000002','wednesday','07:00','19:00',0,'2026-01-11 10:00:00'),
('h0200000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000002','thursday', '07:00','19:00',0,'2026-01-11 10:00:00'),
('h0200000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000002','friday',   '07:00','19:00',0,'2026-01-11 10:00:00'),
('h0200000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000002','saturday', '07:00','17:00',0,'2026-01-11 10:00:00'),
('h0200000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000002','sunday',   NULL,  NULL,  1,'2026-01-11 10:00:00'),
-- BIZ 03: QuickCare Dental — Mon–Sat 9–19, Sun closed
('h0300000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000003','monday',   '09:00','19:00',0,'2026-01-12 10:00:00'),
('h0300000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000003','tuesday',  '09:00','19:00',0,'2026-01-12 10:00:00'),
('h0300000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000003','wednesday','09:00','19:00',0,'2026-01-12 10:00:00'),
('h0300000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000003','thursday', '09:00','19:00',0,'2026-01-12 10:00:00'),
('h0300000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000003','friday',   '09:00','19:00',0,'2026-01-12 10:00:00'),
('h0300000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000003','saturday', '09:00','14:00',0,'2026-01-12 10:00:00'),
('h0300000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000003','sunday',   NULL,  NULL,  1,'2026-01-12 10:00:00'),
-- BIZ 04: LifeStep Physio — Mon–Sat 8–18, Sun closed
('h0400000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000004','monday',   '08:00','18:00',0,'2026-01-13 10:00:00'),
('h0400000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000004','tuesday',  '08:00','18:00',0,'2026-01-13 10:00:00'),
('h0400000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000004','wednesday','08:00','18:00',0,'2026-01-13 10:00:00'),
('h0400000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000004','thursday', '08:00','18:00',0,'2026-01-13 10:00:00'),
('h0400000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000004','friday',   '08:00','18:00',0,'2026-01-13 10:00:00'),
('h0400000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000004','saturday', '09:00','13:00',0,'2026-01-13 10:00:00'),
('h0400000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000004','sunday',   NULL,  NULL,  1,'2026-01-13 10:00:00'),
-- BIZ 05: Glamour Studio — Tue–Sun 10–20, Mon closed
('h0500000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000005','monday',   NULL,  NULL,  1,'2026-01-14 10:00:00'),
('h0500000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000005','tuesday',  '10:00','20:00',0,'2026-01-14 10:00:00'),
('h0500000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000005','wednesday','10:00','20:00',0,'2026-01-14 10:00:00'),
('h0500000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000005','thursday', '10:00','20:00',0,'2026-01-14 10:00:00'),
('h0500000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000005','friday',   '10:00','20:00',0,'2026-01-14 10:00:00'),
('h0500000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000005','saturday', '09:00','21:00',0,'2026-01-14 10:00:00'),
('h0500000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000005','sunday',   '10:00','19:00',0,'2026-01-14 10:00:00'),
-- BIZ 06: Urban Spa — Tue–Sun 10–20, Mon closed
('h0600000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000006','monday',   NULL,  NULL,  1,'2026-01-15 10:00:00'),
('h0600000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000006','tuesday',  '10:00','20:00',0,'2026-01-15 10:00:00'),
('h0600000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000006','wednesday','10:00','20:00',0,'2026-01-15 10:00:00'),
('h0600000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000006','thursday', '10:00','20:00',0,'2026-01-15 10:00:00'),
('h0600000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000006','friday',   '10:00','20:00',0,'2026-01-15 10:00:00'),
('h0600000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000006','saturday', '10:00','21:00',0,'2026-01-15 10:00:00'),
('h0600000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000006','sunday',   '10:00','19:00',0,'2026-01-15 10:00:00'),
-- BIZ 07: Naveen Veg — Mon–Sun 11–22 (Fri–Sat till 23)
('h0700000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000007','monday',   '11:00','22:00',0,'2026-01-16 10:00:00'),
('h0700000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000007','tuesday',  '11:00','22:00',0,'2026-01-16 10:00:00'),
('h0700000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000007','wednesday','11:00','22:00',0,'2026-01-16 10:00:00'),
('h0700000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000007','thursday', '11:00','22:00',0,'2026-01-16 10:00:00'),
('h0700000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000007','friday',   '11:00','23:00',0,'2026-01-16 10:00:00'),
('h0700000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000007','saturday', '10:30','23:00',0,'2026-01-16 10:00:00'),
('h0700000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000007','sunday',   '10:30','22:00',0,'2026-01-16 10:00:00'),
-- BIZ 08: Cafe Aroma — Mon–Fri 7:30–21, Sat–Sun 8–21:30
('h0800000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000008','monday',   '07:30','21:00',0,'2026-01-17 10:00:00'),
('h0800000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000008','tuesday',  '07:30','21:00',0,'2026-01-17 10:00:00'),
('h0800000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000008','wednesday','07:30','21:00',0,'2026-01-17 10:00:00'),
('h0800000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000008','thursday', '07:30','21:00',0,'2026-01-17 10:00:00'),
('h0800000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000008','friday',   '07:30','21:00',0,'2026-01-17 10:00:00'),
('h0800000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000008','saturday', '08:00','21:30',0,'2026-01-17 10:00:00'),
('h0800000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000008','sunday',   '08:00','21:30',0,'2026-01-17 10:00:00'),
-- BIZ 09: Chester Gym — Mon–Fri 6–22, Sat–Sun 7–20
('h0900000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000009','monday',   '06:00','22:00',0,'2026-01-18 10:00:00'),
('h0900000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000009','tuesday',  '06:00','22:00',0,'2026-01-18 10:00:00'),
('h0900000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000009','wednesday','06:00','22:00',0,'2026-01-18 10:00:00'),
('h0900000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000009','thursday', '06:00','22:00',0,'2026-01-18 10:00:00'),
('h0900000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000009','friday',   '06:00','22:00',0,'2026-01-18 10:00:00'),
('h0900000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000009','saturday', '07:00','20:00',0,'2026-01-18 10:00:00'),
('h0900000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000009','sunday',   '07:00','20:00',0,'2026-01-18 10:00:00'),
-- BIZ 10: Sunrise Hotel — 24/7
('h1000000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000010','monday',   '00:00','23:59',0,'2026-01-19 10:00:00'),
('h1000000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000010','tuesday',  '00:00','23:59',0,'2026-01-19 10:00:00'),
('h1000000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000010','wednesday','00:00','23:59',0,'2026-01-19 10:00:00'),
('h1000000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000010','thursday', '00:00','23:59',0,'2026-01-19 10:00:00'),
('h1000000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000010','friday',   '00:00','23:59',0,'2026-01-19 10:00:00'),
('h1000000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000010','saturday', '00:00','23:59',0,'2026-01-19 10:00:00'),
('h1000000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000010','sunday',   '00:00','23:59',0,'2026-01-19 10:00:00'),
-- BIZ 11: Property 852 — Mon–Fri 9–18, Sat 10–14, Sun closed
('h1100000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000011','monday',   '09:00','18:00',0,'2026-01-20 10:00:00'),
('h1100000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000011','tuesday',  '09:00','18:00',0,'2026-01-20 10:00:00'),
('h1100000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000011','wednesday','09:00','18:00',0,'2026-01-20 10:00:00'),
('h1100000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000011','thursday', '09:00','18:00',0,'2026-01-20 10:00:00'),
('h1100000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000011','friday',   '09:00','18:00',0,'2026-01-20 10:00:00'),
('h1100000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000011','saturday', '10:00','14:00',0,'2026-01-20 10:00:00'),
('h1100000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000011','sunday',   NULL,  NULL,  1,'2026-01-20 10:00:00'),
-- BIZ 12: SwiftMove — Mon–Sat 8–20, Sun 9–14
('h1200000-0000-4000-8000-000000000001','c1000000-0000-4000-8000-000000000012','monday',   '08:00','20:00',0,'2026-01-21 10:00:00'),
('h1200000-0000-4000-8000-000000000002','c1000000-0000-4000-8000-000000000012','tuesday',  '08:00','20:00',0,'2026-01-21 10:00:00'),
('h1200000-0000-4000-8000-000000000003','c1000000-0000-4000-8000-000000000012','wednesday','08:00','20:00',0,'2026-01-21 10:00:00'),
('h1200000-0000-4000-8000-000000000004','c1000000-0000-4000-8000-000000000012','thursday', '08:00','20:00',0,'2026-01-21 10:00:00'),
('h1200000-0000-4000-8000-000000000005','c1000000-0000-4000-8000-000000000012','friday',   '08:00','20:00',0,'2026-01-21 10:00:00'),
('h1200000-0000-4000-8000-000000000006','c1000000-0000-4000-8000-000000000012','saturday', '08:00','18:00',0,'2026-01-21 10:00:00'),
('h1200000-0000-4000-8000-000000000007','c1000000-0000-4000-8000-000000000012','sunday',   '09:00','14:00',0,'2026-01-21 10:00:00');


-- =============================================================================
-- 4) SUBCATEGORY ASSIGNMENTS
-- category_id resolved by slug subquery from seed_categories.sql
-- =============================================================================
INSERT IGNORE INTO `mci_business_subcategories`
  (`id`, `business_group_id`, `category_id`, `sort_order`, `created_at`)
VALUES
-- Spark Electricals → Electrical Services
('s0100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000001',
 (SELECT id FROM mci_categories WHERE slug = 'electrical-services' LIMIT 1), 0, '2026-01-10 09:00:00'),
-- CleanNest → Cleaning Services
('s0200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000002',
 (SELECT id FROM mci_categories WHERE slug = 'cleaning-services' LIMIT 1), 0, '2026-01-11 10:00:00'),
-- QuickCare Dental → Dentists
('s0300000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000003',
 (SELECT id FROM mci_categories WHERE slug = 'dentists' LIMIT 1), 0, '2026-01-12 10:00:00'),
-- LifeStep Physio → Physiotherapy
('s0400000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000004',
 (SELECT id FROM mci_categories WHERE slug = 'physiotherapy' LIMIT 1), 0, '2026-01-13 10:00:00'),
-- Glamour Studio → Salons
('s0500000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000005',
 (SELECT id FROM mci_categories WHERE slug = 'salons' LIMIT 1), 0, '2026-01-14 10:00:00'),
-- Urban Spa → Spas
('s0600000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000006',
 (SELECT id FROM mci_categories WHERE slug = 'spas' LIMIT 1), 0, '2026-01-15 10:00:00'),
-- Naveen Famous Veg → Restaurants
('s0700000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000007',
 (SELECT id FROM mci_categories WHERE slug = 'restaurants' LIMIT 1), 0, '2026-01-16 10:00:00'),
-- Cafe Aroma → Cafes
('s0800000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000008',
 (SELECT id FROM mci_categories WHERE slug = 'cafes' LIMIT 1), 0, '2026-01-17 10:00:00'),
-- Chester Gym → Gyms
('s0900000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000009',
 (SELECT id FROM mci_categories WHERE slug = 'gyms' LIMIT 1), 0, '2026-01-18 10:00:00'),
-- Sunrise Hotel Rooms → Hotels
('s1000000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000010',
 (SELECT id FROM mci_categories WHERE slug = 'hotels' LIMIT 1), 0, '2026-01-19 10:00:00'),
-- Property 852 → Commercial Spaces
('s1100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000011',
 (SELECT id FROM mci_categories WHERE slug = 'commercial-spaces' LIMIT 1), 0, '2026-01-20 10:00:00'),
-- SwiftMove Packers → Packers & Movers
('s1200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000012',
 (SELECT id FROM mci_categories WHERE slug = 'packers-movers' LIMIT 1), 0, '2026-01-21 10:00:00')
;


-- =============================================================================
-- 5) TAG ASSIGNMENTS
-- tag_id resolved by slug subquery from seed_tags.sql
-- =============================================================================
INSERT IGNORE INTO `mci_business_tags`
  (`id`, `business_group_id`, `tag_id`, `created_at`)
VALUES
-- Spark Electricals: Certified/Licensed, 24 Hours, Emergency Callout, Doorstep Service, Warranty/Guarantee
('t0100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000001',
 (SELECT id FROM mci_tags WHERE slug = 'certified-licensed' LIMIT 1), '2026-01-10 09:00:00'),
('t0100000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000001',
 (SELECT id FROM mci_tags WHERE slug = 'open-24-hours' LIMIT 1), '2026-01-10 09:00:00'),
('t0100000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000001',
 (SELECT id FROM mci_tags WHERE slug = 'doorstep-service' LIMIT 1), '2026-01-10 09:00:00'),
('t0100000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000001',
 (SELECT id FROM mci_tags WHERE slug = 'free-estimates-quotes' LIMIT 1), '2026-01-10 09:00:00'),
('t0100000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000001',
 (SELECT id FROM mci_tags WHERE slug = 'warranty-guarantee' LIMIT 1), '2026-01-10 09:00:00'),

-- CleanNest: Eco-Friendly, Home Visits, Doorstep Service, Certified/Licensed, Highly Rated
('t0200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000002',
 (SELECT id FROM mci_tags WHERE slug = 'eco-friendly' LIMIT 1), '2026-01-11 10:00:00'),
('t0200000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000002',
 (SELECT id FROM mci_tags WHERE slug = 'home-visits' LIMIT 1), '2026-01-11 10:00:00'),
('t0200000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000002',
 (SELECT id FROM mci_tags WHERE slug = 'doorstep-service' LIMIT 1), '2026-01-11 10:00:00'),
('t0200000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000002',
 (SELECT id FROM mci_tags WHERE slug = 'highly-rated' LIMIT 1), '2026-01-11 10:00:00'),
('t0200000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000002',
 (SELECT id FROM mci_tags WHERE slug = 'same-day-delivery' LIMIT 1), '2026-01-11 10:00:00'),

-- QuickCare Dental: Walk-ins Welcome, Appointment Only, Insurance Accepted, Child-Friendly, Highly Rated
('t0300000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000003',
 (SELECT id FROM mci_tags WHERE slug = 'walk-ins-welcome' LIMIT 1), '2026-01-12 10:00:00'),
('t0300000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000003',
 (SELECT id FROM mci_tags WHERE slug = 'insurance-accepted' LIMIT 1), '2026-01-12 10:00:00'),
('t0300000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000003',
 (SELECT id FROM mci_tags WHERE slug = 'child-friendly' LIMIT 1), '2026-01-12 10:00:00'),
('t0300000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000003',
 (SELECT id FROM mci_tags WHERE slug = 'certified-licensed' LIMIT 1), '2026-01-12 10:00:00'),
('t0300000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000003',
 (SELECT id FROM mci_tags WHERE slug = 'upi-accepted' LIMIT 1), '2026-01-12 10:00:00'),

-- LifeStep Physio: Home Visits, Appointment Only, Insurance Accepted, Female Staff, Experienced Professionals
('t0400000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000004',
 (SELECT id FROM mci_tags WHERE slug = 'home-visits' LIMIT 1), '2026-01-13 10:00:00'),
('t0400000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000004',
 (SELECT id FROM mci_tags WHERE slug = 'appointment-only' LIMIT 1), '2026-01-13 10:00:00'),
('t0400000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000004',
 (SELECT id FROM mci_tags WHERE slug = 'insurance-accepted' LIMIT 1), '2026-01-13 10:00:00'),
('t0400000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000004',
 (SELECT id FROM mci_tags WHERE slug = 'female-staff-available' LIMIT 1), '2026-01-13 10:00:00'),
('t0400000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000004',
 (SELECT id FROM mci_tags WHERE slug = 'experienced-professionals' LIMIT 1), '2026-01-13 10:00:00'),

-- Glamour Studio: AC/Air-Conditioned, WiFi, UPI, Premium/Luxury, Female Staff, Appointment Only
('t0500000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000005',
 (SELECT id FROM mci_tags WHERE slug = 'ac-air-conditioned' LIMIT 1), '2026-01-14 10:00:00'),
('t0500000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000005',
 (SELECT id FROM mci_tags WHERE slug = 'upi-accepted' LIMIT 1), '2026-01-14 10:00:00'),
('t0500000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000005',
 (SELECT id FROM mci_tags WHERE slug = 'premium-luxury' LIMIT 1), '2026-01-14 10:00:00'),
('t0500000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000005',
 (SELECT id FROM mci_tags WHERE slug = 'female-staff-available' LIMIT 1), '2026-01-14 10:00:00'),
('t0500000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000005',
 (SELECT id FROM mci_tags WHERE slug = 'for-women' LIMIT 1), '2026-01-14 10:00:00'),

-- Urban Spa: Premium/Luxury, AC, Private Rooms, Female Staff, For Couples, UPI
('t0600000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000006',
 (SELECT id FROM mci_tags WHERE slug = 'premium-luxury' LIMIT 1), '2026-01-15 10:00:00'),
('t0600000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000006',
 (SELECT id FROM mci_tags WHERE slug = 'private-rooms-cabins' LIMIT 1), '2026-01-15 10:00:00'),
('t0600000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000006',
 (SELECT id FROM mci_tags WHERE slug = 'for-couples' LIMIT 1), '2026-01-15 10:00:00'),
('t0600000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000006',
 (SELECT id FROM mci_tags WHERE slug = 'female-staff-available' LIMIT 1), '2026-01-15 10:00:00'),
('t0600000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000006',
 (SELECT id FROM mci_tags WHERE slug = 'ac-air-conditioned' LIMIT 1), '2026-01-15 10:00:00'),

-- Naveen Veg: Pure Veg, Jain Food, Outdoor Seating, Home-Style Food, Family Business, UPI, Child-Friendly
('t0700000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000007',
 (SELECT id FROM mci_tags WHERE slug = 'pure-veg' LIMIT 1), '2026-01-16 10:00:00'),
('t0700000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000007',
 (SELECT id FROM mci_tags WHERE slug = 'jain-food' LIMIT 1), '2026-01-16 10:00:00'),
('t0700000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000007',
 (SELECT id FROM mci_tags WHERE slug = 'home-style-food' LIMIT 1), '2026-01-16 10:00:00'),
('t0700000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000007',
 (SELECT id FROM mci_tags WHERE slug = 'family-business' LIMIT 1), '2026-01-16 10:00:00'),
('t0700000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000007',
 (SELECT id FROM mci_tags WHERE slug = 'child-friendly' LIMIT 1), '2026-01-16 10:00:00'),

-- Cafe Aroma: WiFi, Vegan Options, Outdoor Seating, UPI, Locally Owned, Early Morning Hours
('t0800000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000008',
 (SELECT id FROM mci_tags WHERE slug = 'wifi-available' LIMIT 1), '2026-01-17 10:00:00'),
('t0800000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000008',
 (SELECT id FROM mci_tags WHERE slug = 'vegan-options' LIMIT 1), '2026-01-17 10:00:00'),
('t0800000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000008',
 (SELECT id FROM mci_tags WHERE slug = 'outdoor-seating' LIMIT 1), '2026-01-17 10:00:00'),
('t0800000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000008',
 (SELECT id FROM mci_tags WHERE slug = 'early-morning-hours' LIMIT 1), '2026-01-17 10:00:00'),
('t0800000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000008',
 (SELECT id FROM mci_tags WHERE slug = 'locally-owned' LIMIT 1), '2026-01-17 10:00:00'),

-- Chester Gym: AC, Parking, Certified/Licensed, For Women, Open on Sundays, Child-Friendly
('t0900000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000009',
 (SELECT id FROM mci_tags WHERE slug = 'ac-air-conditioned' LIMIT 1), '2026-01-18 10:00:00'),
('t0900000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000009',
 (SELECT id FROM mci_tags WHERE slug = 'parking-available' LIMIT 1), '2026-01-18 10:00:00'),
('t0900000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000009',
 (SELECT id FROM mci_tags WHERE slug = 'certified-licensed' LIMIT 1), '2026-01-18 10:00:00'),
('t0900000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000009',
 (SELECT id FROM mci_tags WHERE slug = 'open-on-sundays' LIMIT 1), '2026-01-18 10:00:00'),
('t0900000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000009',
 (SELECT id FROM mci_tags WHERE slug = 'for-women' LIMIT 1), '2026-01-18 10:00:00'),

-- Sunrise Hotel: Open 24 Hours, Parking, WiFi, AC, Budget-Friendly, Corporate Clients
('t1000000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000010',
 (SELECT id FROM mci_tags WHERE slug = 'open-24-hours' LIMIT 1), '2026-01-19 10:00:00'),
('t1000000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000010',
 (SELECT id FROM mci_tags WHERE slug = 'parking-available' LIMIT 1), '2026-01-19 10:00:00'),
('t1000000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000010',
 (SELECT id FROM mci_tags WHERE slug = 'wifi-available' LIMIT 1), '2026-01-19 10:00:00'),
('t1000000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000010',
 (SELECT id FROM mci_tags WHERE slug = 'budget-friendly' LIMIT 1), '2026-01-19 10:00:00'),
('t1000000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000010',
 (SELECT id FROM mci_tags WHERE slug = 'corporate-clients' LIMIT 1), '2026-01-19 10:00:00'),

-- Property 852: Premium/Luxury, Corporate Clients, Experienced Professionals, Multilingual Staff, Free Consultation
('t1100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000011',
 (SELECT id FROM mci_tags WHERE slug = 'premium-luxury' LIMIT 1), '2026-01-20 10:00:00'),
('t1100000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000011',
 (SELECT id FROM mci_tags WHERE slug = 'corporate-clients' LIMIT 1), '2026-01-20 10:00:00'),
('t1100000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000011',
 (SELECT id FROM mci_tags WHERE slug = 'experienced-professionals' LIMIT 1), '2026-01-20 10:00:00'),
('t1100000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000011',
 (SELECT id FROM mci_tags WHERE slug = 'multilingual-staff' LIMIT 1), '2026-01-20 10:00:00'),
('t1100000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000011',
 (SELECT id FROM mci_tags WHERE slug = 'free-consultation' LIMIT 1), '2026-01-20 10:00:00'),

-- SwiftMove: Home Delivery, Same Day Delivery, Open on Sundays, Warranty/Guarantee, No Hidden Charges
('t1200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000012',
 (SELECT id FROM mci_tags WHERE slug = 'home-delivery' LIMIT 1), '2026-01-21 10:00:00'),
('t1200000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000012',
 (SELECT id FROM mci_tags WHERE slug = 'same-day-delivery' LIMIT 1), '2026-01-21 10:00:00'),
('t1200000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000012',
 (SELECT id FROM mci_tags WHERE slug = 'open-on-sundays' LIMIT 1), '2026-01-21 10:00:00'),
('t1200000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000012',
 (SELECT id FROM mci_tags WHERE slug = 'no-hidden-charges' LIMIT 1), '2026-01-21 10:00:00'),
('t1200000-0000-4000-8000-000000000005','b1000000-0000-4000-8000-000000000012',
 (SELECT id FROM mci_tags WHERE slug = 'warranty-guarantee' LIMIT 1), '2026-01-21 10:00:00');


-- =============================================================================
-- 6) PRODUCTS
-- =============================================================================
INSERT IGNORE INTO `mci_business_products`
  (`id`, `business_group_id`, `name`, `description`, `price_min`, `price_max`, `price_unit`, `sort_order`, `is_active`, `created_at`)
VALUES
-- Spark Electricals
('p0100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000001',
 'Switchboard / MCB Box','Standard MCB switchboard replacement with 6–12 way distribution board.',2500.00,6500.00,'INR',0,1,'2026-01-10 09:00:00'),
('p0100000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000001',
 'Ceiling Fan Installation','Supply and installation of ceiling fan with regulator. Fan not included.',350.00,550.00,'INR per fan',1,1,'2026-01-10 09:00:00'),
-- CleanNest
('p0200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000002',
 'Deep Cleaning — 1BHK','Full deep clean of 1BHK apartment: kitchen, bathroom, floors and surfaces.',1499.00,1999.00,'INR per visit',0,1,'2026-01-11 10:00:00'),
('p0200000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000002',
 'Deep Cleaning — 2BHK','Full deep clean of 2BHK apartment including all rooms, kitchen and bathrooms.',2299.00,2899.00,'INR per visit',1,1,'2026-01-11 10:00:00'),
('p0200000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000002',
 'Sofa Cleaning (3-Seater)','Professional dry and wet sofa cleaning with fabric sanitisation.',799.00,1099.00,'INR per sofa',2,1,'2026-01-11 10:00:00'),
-- Chester Gym
('p0900000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000009',
 'Monthly Membership','Unlimited gym floor access, locker and towel service included.',29.99,49.99,'GBP per month',0,1,'2026-01-18 10:00:00'),
('p0900000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000009',
 'Annual Membership','12-month gym access. Save 20% vs monthly. Includes 1 free PT induction.',299.00,459.00,'GBP per year',1,1,'2026-01-18 10:00:00'),
-- Sunrise Hotel
('p1000000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000010',
 'Standard Single Room','AC single room with TV, Wi-Fi and ensuite bathroom. Breakfast available.',799.00,999.00,'INR per night',0,1,'2026-01-19 10:00:00'),
('p1000000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000010',
 'Deluxe Double Room','Spacious double room with city view, king bed, minibar and breakfast included.',1299.00,1599.00,'INR per night',1,1,'2026-01-19 10:00:00'),
-- Cafe Aroma
('p0800000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000008',
 'Signature Espresso','Single-origin Coorg Arabica double espresso. Served with sparkling water.',120.00,NULL,'INR',0,1,'2026-01-17 10:00:00'),
('p0800000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000008',
 'All-Day Breakfast Platter','2 eggs any style, sourdough toast, grilled tomato, mushrooms and baked beans.',320.00,NULL,'INR per platter',1,1,'2026-01-17 10:00:00'),
-- Urban Spa
('p0600000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000006',
 'Swedish Massage (60 min)','Full-body relaxation massage with warm oils. Ideal for stress relief.',1800.00,NULL,'INR per session',0,1,'2026-01-15 10:00:00'),
('p0600000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000006',
 'Couple''s Pamper Package','2-hour shared experience: aromatherapy massage + facial + welcome drinks.',3800.00,NULL,'INR per couple',1,1,'2026-01-15 10:00:00');


-- =============================================================================
-- 7) SERVICES
-- =============================================================================
INSERT IGNORE INTO `mci_business_services`
  (`id`, `business_group_id`, `name`, `description`, `price_min`, `price_max`, `price_unit`, `sort_order`, `is_active`, `created_at`)
VALUES
-- Spark Electricals
('v0100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000001',
 'Residential Wiring','New wiring, rewiring and switchboard upgrades for homes and apartments.',3000.00,25000.00,'INR per project',0,1,'2026-01-10 09:00:00'),
('v0100000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000001',
 'Electrical Safety Inspection','Comprehensive safety audit with written report and compliance certificate.',1500.00,3000.00,'INR per inspection',1,1,'2026-01-10 09:00:00'),
('v0100000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000001',
 '24/7 Emergency Callout','Emergency fault diagnosis and repair. 2-hour response across Bangalore.',1200.00,NULL,'INR per visit',2,1,'2026-01-10 09:00:00'),
-- CleanNest
('v0200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000002',
 'Move-In / Move-Out Cleaning','Full property deep clean before or after tenancy.',2499.00,4999.00,'INR per visit',0,1,'2026-01-11 10:00:00'),
('v0200000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000002',
 'Office Cleaning (Monthly Contract)','Daily or weekly office cleaning on a monthly contract basis.',4999.00,14999.00,'INR per month',1,1,'2026-01-11 10:00:00'),
('v0200000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000002',
 'Post-Renovation Cleaning','Deep clean of construction dust, paint splatter and debris post-renovation.',2999.00,6999.00,'INR per visit',2,1,'2026-01-11 10:00:00'),
-- QuickCare Dental
('v0300000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000003',
 'Dental Checkup & Clean','Comprehensive examination, X-rays, scale and clean with fluoride treatment.',700.00,1200.00,'INR per visit',0,1,'2026-01-12 10:00:00'),
('v0300000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000003',
 'Teeth Whitening','In-clinic LED accelerated whitening. Noticeable results in 1 session.',3500.00,6000.00,'INR per session',1,1,'2026-01-12 10:00:00'),
('v0300000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000003',
 'Braces / Orthodontics','Metal braces or clear aligners. Free initial consultation included.',25000.00,85000.00,'INR full treatment',2,1,'2026-01-12 10:00:00'),
-- LifeStep Physio
('v0400000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000004',
 'Initial Assessment','60-min physiotherapy assessment, diagnosis and personalised treatment plan.',1200.00,NULL,'INR per session',0,1,'2026-01-13 10:00:00'),
('v0400000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000004',
 'Follow-Up Treatment','45-min hands-on physiotherapy and manual therapy session.',800.00,NULL,'INR per session',1,1,'2026-01-13 10:00:00'),
('v0400000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000004',
 'Dry Needling','Targeted dry needling for muscle pain and trigger point release.',950.00,NULL,'INR per session',2,1,'2026-01-13 10:00:00'),
('v0400000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000004',
 'Home Visit Physiotherapy','In-home physio session for patients unable to travel to clinic.',1500.00,NULL,'INR per visit',3,1,'2026-01-13 10:00:00'),
-- Glamour Studio
('v0500000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000005',
 'Haircut & Styling','Expert haircut and blow-dry by senior stylist.',500.00,1500.00,'INR',0,1,'2026-01-14 10:00:00'),
('v0500000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000005',
 'Hair Colouring (Global)','Full global hair colour with premium international brand products.',2500.00,6000.00,'INR',1,1,'2026-01-14 10:00:00'),
('v0500000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000005',
 'Keratin Smoothing Treatment','Professional keratin treatment for frizz-free, smooth hair for 3–5 months.',4000.00,9000.00,'INR',2,1,'2026-01-14 10:00:00'),
('v0500000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000005',
 'Bridal Package','Full bridal hair and makeup package for the big day. Includes trial session.',15000.00,35000.00,'INR per package',3,1,'2026-01-14 10:00:00'),
-- Urban Spa
('v0600000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000006',
 'Deep Tissue Massage (90 min)','Therapeutic deep-tissue massage targeting chronic muscle tension.',2400.00,NULL,'INR per session',0,1,'2026-01-15 10:00:00'),
('v0600000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000006',
 'Hydrating Facial','60-min hydrating and brightening facial with premium serums and mask.',1600.00,NULL,'INR per session',1,1,'2026-01-15 10:00:00'),
('v0600000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000006',
 'Ayurvedic Abhyanga','Traditional full-body Ayurvedic oil massage by two therapists.',3200.00,NULL,'INR per session',2,1,'2026-01-15 10:00:00'),
-- Naveen Veg
('v0700000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000007',
 'Event Catering','Full-service vegetarian catering for weddings, corporate events and functions.',300.00,600.00,'INR per plate',0,1,'2026-01-16 10:00:00'),
('v0700000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000007',
 'Daily Tiffin Service','1 sabzi, dal, rice, 3 rotis, pickle. Monthly subscription available.',120.00,NULL,'INR per day',1,1,'2026-01-16 10:00:00'),
-- Cafe Aroma
('v0800000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000008',
 'Barista Workshop','3-hour hands-on barista training: espresso, milk steaming and latte art. Max 8 pax.',1200.00,NULL,'INR per person',0,1,'2026-01-17 10:00:00'),
('v0800000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000008',
 'Private Event Hire','Full cafe buyout for private events, launches and corporate breakfasts. Min 2 hrs.',8000.00,20000.00,'INR per event',1,1,'2026-01-17 10:00:00'),
-- Chester Gym
('v0900000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000009',
 'Personal Training Session','1-on-1 certified personal training. Goal-based programming.',45.00,65.00,'GBP per session',0,1,'2026-01-18 10:00:00'),
('v0900000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000009',
 'Group Fitness Class','Drop-in to HIIT, yoga, spin, Zumba and bootcamp classes.',8.00,12.00,'GBP per class',1,1,'2026-01-18 10:00:00'),
-- Sunrise Hotel
('v1000000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000010',
 'Room Service','In-room dining 7 AM – 11 PM. Indian and continental menu.',NULL,NULL,NULL,0,1,'2026-01-19 10:00:00'),
('v1000000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000010',
 'Airport Transfer','Pre-booked AC car to/from Patna Airport.',600.00,900.00,'INR per trip',1,1,'2026-01-19 10:00:00'),
('v1000000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000010',
 'Conference Room Hire','Full-day conference room for up to 30 guests. AV equipment included.',3500.00,6000.00,'INR per day',2,1,'2026-01-19 10:00:00'),
-- Property 852
('v1100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000011',
 'Leasing Advisory','End-to-end leasing consultation, negotiations and documentation.',NULL,NULL,'Free consultation',0,1,'2026-01-20 10:00:00'),
('v1100000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000011',
 'Property Valuation','Independent market valuation for commercial and residential properties.',5000.00,15000.00,'HKD per report',1,1,'2026-01-20 10:00:00'),
-- SwiftMove
('v1200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000012',
 'Home Shifting (Local)','Packing, loading, transport and unloading within the same city.',2999.00,8999.00,'INR',0,1,'2026-01-21 10:00:00'),
('v1200000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000012',
 'Home Shifting (Interstate)','Pan-India relocation with full packing, GPS tracking and insurance.',8999.00,35000.00,'INR',1,1,'2026-01-21 10:00:00'),
('v1200000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000012',
 'Vehicle Transportation','Car or bike transport on enclosed carrier. GPS-tracked and insured.',4999.00,12999.00,'INR',2,1,'2026-01-21 10:00:00'),
('v1200000-0000-4000-8000-000000000004','b1000000-0000-4000-8000-000000000012',
 'Warehouse Storage','Short and long-term storage in climate-controlled warehouse facility.',1500.00,5000.00,'INR per month',3,1,'2026-01-21 10:00:00');


-- =============================================================================
-- 8) FAQs (2 per business)
-- =============================================================================
INSERT IGNORE INTO `mci_business_faqs`
  (`id`, `business_group_id`, `question`, `answer`, `sort_order`, `created_at`)
VALUES
-- Spark Electricals
('f0100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000001',
 'Are your electricians licensed and insured?',
 'Yes, all our electricians hold valid Karnataka state electrical licences and we carry full public liability insurance for all residential and commercial work.',
 0,'2026-01-10 09:00:00'),
('f0100000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000001',
 'How quickly can you respond to an emergency?',
 'Our 24/7 emergency team guarantees a response within 2 hours across Bangalore. Call our dedicated emergency line for immediate dispatch.',
 1,'2026-01-10 09:00:00'),
-- CleanNest
('f0200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000002',
 'Are the cleaning products safe for children and pets?',
 'Yes, we use eco-certified, non-toxic cleaning products that are completely safe for children and pets. We can also use your preferred products on request.',
 0,'2026-01-11 10:00:00'),
('f0200000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000002',
 'Do I need to be home during the cleaning?',
 'You do not need to be present. Many of our regular clients provide a spare key or access code. All our staff are police-verified and insured.',
 1,'2026-01-11 10:00:00'),
-- QuickCare Dental
('f0300000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000003',
 'Do you treat children?',
 'Yes, we welcome patients of all ages from 3 years and above. Our team has extensive experience in paediatric dentistry and makes children feel at ease.',
 0,'2026-01-12 10:00:00'),
('f0300000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000003',
 'Do you accept insurance for dental treatments?',
 'Yes, we accept most major health insurance and cashless mediclaim policies. Please bring your insurance card and we will handle the claim process directly.',
 1,'2026-01-12 10:00:00'),
-- LifeStep Physio
('f0400000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000004',
 'Do I need a doctor referral to book physiotherapy?',
 'No referral is needed. You can book directly by phone or via our website for an initial assessment. We will liaise with your doctor if needed.',
 0,'2026-01-13 10:00:00'),
('f0400000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000004',
 'Do you offer home visit physiotherapy?',
 'Yes, we offer home visit sessions across Hyderabad for patients who are unable to travel. Please book at least 24 hours in advance for home visits.',
 1,'2026-01-13 10:00:00'),
-- Glamour Studio
('f0500000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000005',
 'Do I need to book an appointment?',
 'Appointments are strongly recommended, especially on weekends. Walk-ins are accepted subject to availability. Book online or call us directly.',
 0,'2026-01-14 10:00:00'),
('f0500000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000005',
 'What hair care products do you use?',
 'We exclusively use professional-grade products from Wella, L''Oréal Professionnel, Schwarzkopf and Olaplex for all colouring and treatment services.',
 1,'2026-01-14 10:00:00'),
-- Urban Spa
('f0600000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000006',
 'Should I book in advance?',
 'Advance booking is strongly recommended, especially for weekends and couple packages. Walk-ins are welcome subject to therapist availability.',
 0,'2026-01-15 10:00:00'),
('f0600000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000006',
 'Are your therapists trained and certified?',
 'All our therapists hold nationally recognised certificates with a minimum of 3 years of professional experience in massage and beauty therapy.',
 1,'2026-01-15 10:00:00'),
-- Naveen Veg
('f0700000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000007',
 'Is your kitchen 100% vegetarian?',
 'Yes, our kitchen is strictly pure vegetarian with no eggs. We use no non-vegetarian ingredients or shared cooking equipment. Jain food is available on request.',
 0,'2026-01-16 10:00:00'),
('f0700000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000007',
 'How far in advance should I book catering?',
 'For events up to 100 guests, 7 days notice is required. For larger events please contact us at least 3–4 weeks in advance to ensure quality and availability.',
 1,'2026-01-16 10:00:00'),
-- Cafe Aroma
('f0800000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000008',
 'Do you offer dairy-free or vegan options?',
 'Yes, we offer oat, almond and soy milk alternatives for all espresso drinks at no extra charge. Several breakfast items are fully vegan — ask our staff.',
 0,'2026-01-17 10:00:00'),
('f0800000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000008',
 'Is there Wi-Fi available?',
 'Yes, free high-speed Wi-Fi is available for all customers. The password is displayed at the counter. We only ask that tables be turned over after 90 minutes during peak hours.',
 1,'2026-01-17 10:00:00'),
-- Chester Gym
('f0900000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000009',
 'Is there a joining fee?',
 'There is no joining fee on annual memberships. Monthly memberships have a one-off £10 admin charge. Student and senior discounts are available.',
 0,'2026-01-18 10:00:00'),
('f0900000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000009',
 'Can I freeze my membership?',
 'Yes, members can freeze their membership for up to 3 months per year with 7 days notice. Perfect for holidays or injury recovery.',
 1,'2026-01-18 10:00:00'),
-- Sunrise Hotel
('f1000000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000010',
 'What time is check-in and check-out?',
 'Check-in is from 12:00 noon. Check-out is by 11:00 AM. Early check-in and late check-out are available on request subject to availability.',
 0,'2026-01-19 10:00:00'),
('f1000000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000010',
 'Is breakfast included in the room rate?',
 'Breakfast is included in our Deluxe room packages. Standard room guests can add breakfast for ₹150 per person per day at the time of booking.',
 1,'2026-01-19 10:00:00'),
-- Property 852
('f1100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000011',
 'What is the minimum lease term for office space?',
 'We offer flexible lease terms starting from 6 months for fitted spaces. Shell-and-core offices typically require a minimum 12–24 month commitment.',
 0,'2026-01-20 10:00:00'),
('f1100000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000011',
 'Do you assist with lease negotiations?',
 'Yes, our bilingual advisory team handles all aspects of lease negotiation, legal review and documentation on behalf of both tenants and landlords.',
 1,'2026-01-20 10:00:00'),
-- SwiftMove
('f1200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000012',
 'Are my belongings insured during transit?',
 'Yes, all moves are covered by transit insurance. You can optionally upgrade to comprehensive coverage for high-value items at an additional premium.',
 0,'2026-01-21 10:00:00'),
('f1200000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000012',
 'How far in advance should I book?',
 'We recommend booking at least 3–5 days in advance for local moves and 7–10 days for interstate relocations. Same-day local moves are available subject to availability.',
 1,'2026-01-21 10:00:00');


-- =============================================================================
-- 9) SOCIAL LINKS
-- =============================================================================
INSERT IGNORE INTO `mci_business_social_links`
  (`id`, `business_group_id`, `platform`, `url`, `sort_order`, `created_at`)
VALUES
-- Spark Electricals
('l0100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000001','facebook',  'https://facebook.com/sparkelectricalsBLR',0,'2026-01-10 09:00:00'),
('l0100000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000001','instagram', 'https://instagram.com/sparkelectricals',  1,'2026-01-10 09:00:00'),
-- CleanNest
('l0200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000002','facebook',  'https://facebook.com/cleannestpune',      0,'2026-01-11 10:00:00'),
('l0200000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000002','instagram', 'https://instagram.com/cleannesthome',     1,'2026-01-11 10:00:00'),
-- QuickCare Dental
('l0300000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000003','facebook',  'https://facebook.com/quickcaredental',    0,'2026-01-12 10:00:00'),
('l0300000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000003','instagram', 'https://instagram.com/quickcaredental',   1,'2026-01-12 10:00:00'),
-- LifeStep Physio
('l0400000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000004','facebook',  'https://facebook.com/lifestepphysio',     0,'2026-01-13 10:00:00'),
('l0400000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000004','instagram', 'https://instagram.com/lifestepphysio',    1,'2026-01-13 10:00:00'),
-- Glamour Studio
('l0500000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000005','instagram', 'https://instagram.com/glamourstudiodelhi',0,'2026-01-14 10:00:00'),
('l0500000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000005','facebook',  'https://facebook.com/glamourstudiodelhi', 1,'2026-01-14 10:00:00'),
('l0500000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000005','youtube',   'https://youtube.com/@glamourstudio',      2,'2026-01-14 10:00:00'),
-- Urban Spa
('l0600000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000006','instagram', 'https://instagram.com/urbanspahouse',     0,'2026-01-15 10:00:00'),
('l0600000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000006','facebook',  'https://facebook.com/urbanspahouse',      1,'2026-01-15 10:00:00'),
-- Naveen Veg
('l0700000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000007','facebook',  'https://facebook.com/naveenfamousveg',   0,'2026-01-16 10:00:00'),
('l0700000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000007','instagram', 'https://instagram.com/naveenfamousveg',  1,'2026-01-16 10:00:00'),
-- Cafe Aroma
('l0800000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000008','instagram', 'https://instagram.com/cafearomadelhi',   0,'2026-01-17 10:00:00'),
('l0800000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000008','facebook',  'https://facebook.com/cafearomadelhi',    1,'2026-01-17 10:00:00'),
-- Chester Gym
('l0900000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000009','facebook',  'https://facebook.com/chestergymfitness', 0,'2026-01-18 10:00:00'),
('l0900000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000009','instagram', 'https://instagram.com/chestergym',       1,'2026-01-18 10:00:00'),
('l0900000-0000-4000-8000-000000000003','b1000000-0000-4000-8000-000000000009','youtube',   'https://youtube.com/@chestergymfitness', 2,'2026-01-18 10:00:00'),
-- Sunrise Hotel
('l1000000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000010','facebook',  'https://facebook.com/sunrisehotelpatna', 0,'2026-01-19 10:00:00'),
-- Property 852
('l1100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000011','linkedin',  'https://linkedin.com/company/property852',0,'2026-01-20 10:00:00'),
('l1100000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000011','facebook',  'https://facebook.com/property852hk',     1,'2026-01-20 10:00:00'),
-- SwiftMove
('l1200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000012','facebook',  'https://facebook.com/swiftmoveindia',    0,'2026-01-21 10:00:00'),
('l1200000-0000-4000-8000-000000000002','b1000000-0000-4000-8000-000000000012','instagram', 'https://instagram.com/swiftmoveindia',   1,'2026-01-21 10:00:00');


-- =============================================================================
-- 10) APPROVAL LOG  (cp_admin → live immediately)
-- =============================================================================
INSERT IGNORE INTO `mci_business_approvals`
  (`id`, `business_group_id`, `reviewed_by_user_id`, `action`, `previous_status`, `new_status`, `notes`, `reviewed_at`)
VALUES
('a0100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000001','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-10 09:00:00'),
('a0200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000002','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-11 10:00:00'),
('a0300000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000003','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-12 10:00:00'),
('a0400000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000004','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-13 10:00:00'),
('a0500000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000005','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-14 10:00:00'),
('a0600000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000006','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-15 10:00:00'),
('a0700000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000007','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-16 10:00:00'),
('a0800000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000008','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-17 10:00:00'),
('a0900000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000009','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-18 10:00:00'),
('a1000000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000010','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-19 10:00:00'),
('a1100000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000011','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-20 10:00:00'),
('a1200000-0000-4000-8000-000000000001','b1000000-0000-4000-8000-000000000012','e0000000-0000-4000-8000-000000000010','approved',NULL,'live','Seeded by system admin.','2026-01-21 10:00:00');


SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Seed complete.
-- 12 businesses, 12 branches, 84 branch hours, subcategory + tag assignments,
-- products, services, FAQs, social links, approval log.
-- All categories and tags resolved from seed_categories.sql / seed_tags.sql slugs.
-- =============================================================================
