-- MyCityInfo Category Taxonomy — ready to insert
-- Generated: 2026-03-21
-- 22 parent categories + 163 subcategories = 185 total rows
-- Run against your MySQL/MariaDB database.
-- Safe to run on an empty mci_categories table.
-- To re-run: SET foreign_key_checks=0; TRUNCATE TABLE mci_categories; SET foreign_key_checks=1;

SET foreign_key_checks=0;
SET NAMES utf8mb4;
START TRANSACTION;

-- ============================================================
-- PARENT CATEGORIES (22 total)
-- ============================================================

INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(NULL, 'Home Services', 'home-services', '🏠', 10,
 'Find trusted local professionals for all your home repair, maintenance, and improvement needs. From plumbing to interior design, get your home sorted.',
 'Home Services Near You | My City Info',
 'home services, home repair, home maintenance, home improvement, local handyman, house services, home professionals, repair services',
 'Browse top-rated home service professionals in your city. Find plumbers, electricians, cleaners, and more — all vetted and ready to help.'),

(NULL, 'Health & Medical', 'health-medical', '🏥', 20,
 'Connect with trusted healthcare providers including doctors, hospitals, diagnostic labs, and specialists. Your health is our priority.',
 'Health & Medical Services Near You | My City Info',
 'hospitals near me, doctors near me, medical services, health care, clinics, diagnostic labs, pharmacies, specialists',
 'Find the best hospitals, clinics, doctors, and medical services in your city. Book appointments and get quality healthcare close to home.'),

(NULL, 'Beauty & Personal Care', 'beauty-personal-care', '💄', 30,
 'Discover top salons, spas, and personal care services to look and feel your best. From haircuts to bridal makeovers, beauty is just around the corner.',
 'Beauty & Personal Care Services Near You | My City Info',
 'salons near me, spas, beauty services, makeup artists, bridal services, hair care, skincare, nail art, wellness',
 'Explore the best salons, spas, and beauty professionals in your city. Book beauty and personal care services with ease on My City Info.'),

(NULL, 'Food & Restaurants', 'food-restaurants', '🍽️', 40,
 'Explore the best restaurants, cafes, street food, and catering services in your city. Whether dining in or ordering out, find your perfect meal.',
 'Food & Restaurants Near You | My City Info',
 'restaurants near me, food delivery, cafes, catering, street food, bakeries, dining, cloud kitchen, fast food',
 'Discover top restaurants, cafes, bakeries, and food services in your city. Explore menus, cuisines, and order from the best local eateries.'),

(NULL, 'Shopping & Retail', 'shopping-retail', '🛍️', 50,
 'Shop local at a wide range of stores including clothing, electronics, groceries, and more. Support local businesses and find great deals in your city.',
 'Shopping & Retail Stores Near You | My City Info',
 'shopping near me, retail stores, clothing stores, electronics shops, grocery stores, local shopping, supermarkets, furniture stores',
 'Find the best local shops and retail stores in your city. Browse clothing, electronics, groceries, jewellery, and more on My City Info.'),

(NULL, 'Automotive', 'automotive', '🚗', 60,
 'Find reliable automotive services including car and bike repair, rentals, dealers, and accessories. Keep your vehicle in top shape with local experts.',
 'Automotive Services Near You | My City Info',
 'car repair near me, bike repair, car service, auto dealers, car wash, car rentals, driving school, spare parts, EV charging',
 'Discover top automotive service providers in your city. Find garages, car wash, rentals, dealers, and more on My City Info.'),

(NULL, 'Professional Services', 'professional-services', '🏢', 70,
 'Connect with skilled professionals including lawyers, accountants, consultants, and IT experts. Get expert advice and services for your business needs.',
 'Professional Services Near You | My City Info',
 'professional services, chartered accountants, lawyers, consultants, IT services, digital marketing, business consultants, tax consultants',
 'Find experienced professionals in your city for legal, financial, IT, and business services. Grow your business with trusted local experts.'),

(NULL, 'Education & Training', 'education-training', '🎓', 80,
 'Discover schools, colleges, coaching centres, and skill development programmes. Empower yourself and your family with quality education nearby.',
 'Education & Training Near You | My City Info',
 'schools near me, coaching classes, tuition centres, colleges, skill development, online courses, music classes, dance classes, education',
 'Find the best schools, colleges, coaching institutes, and training centres in your city. Explore learning opportunities on My City Info.'),

(NULL, 'Fitness & Sports', 'fitness-sports', '🏋️', 90,
 'Explore gyms, yoga studios, sports clubs, and fitness centres to achieve your health goals. Stay active with top-rated fitness services near you.',
 'Fitness & Sports Services Near You | My City Info',
 'gyms near me, yoga classes, fitness centres, sports clubs, swimming pools, personal trainer, martial arts, zumba, cricket academy',
 'Find the best gyms, yoga studios, sports facilities, and fitness trainers in your city. Start your wellness journey with My City Info.'),

(NULL, 'Travel & Hospitality', 'travel-hospitality', '🏨', 100,
 'Plan your perfect trip with top hotels, travel agencies, tour operators, and hospitality services. Explore the world from your city.',
 'Travel & Hospitality Services Near You | My City Info',
 'hotels near me, travel agencies, tour operators, homestays, resorts, car rentals, visa services, ticket booking, travel',
 'Book hotels, resorts, travel packages, and hospitality services in your city. Plan your next trip effortlessly with My City Info.'),

(NULL, 'Real Estate', 'real-estate', '🏗️', 110,
 'Find trusted property dealers, builders, rental services, and real estate consultants. Whether buying, selling, or renting — we have you covered.',
 'Real Estate Services Near You | My City Info',
 'property dealers, real estate, builders, rental services, PG hostels, commercial spaces, property consultants, apartments for rent',
 'Discover top real estate agents, builders, and rental services in your city. Buy, sell, or rent property with confidence on My City Info.'),

(NULL, 'Logistics & Delivery', 'logistics-delivery', '📦', 120,
 'Find reliable courier, moving, and logistics services for your home and business needs. Fast, safe, and affordable delivery solutions near you.',
 'Logistics & Delivery Services Near You | My City Info',
 'courier services, packers and movers, transport services, warehousing, last mile delivery, logistics, shipping, relocation',
 'Find trusted courier services, packers and movers, and logistics providers in your city. Ship, move, and deliver with ease on My City Info.'),

(NULL, 'Events & Entertainment', 'events-entertainment', '🎉', 130,
 'Discover event planners, wedding services, photographers, and entertainment venues for every occasion. Make every event memorable.',
 'Events & Entertainment Services Near You | My City Info',
 'event planners, wedding planners, photographers, DJs, party halls, decorators, caterers, entertainment, events near me',
 'Find top event planners, wedding services, photographers, and entertainment providers in your city. Plan unforgettable events with My City Info.'),

(NULL, 'Pets & Animals', 'pets-animals', '🐾', 140,
 'Care for your beloved pets with top-rated veterinary clinics, grooming, boarding, and pet supply shops. Your pet deserves the best.',
 'Pet Services & Shops Near You | My City Info',
 'pet shops, veterinary clinics, pet grooming, pet boarding, pet training, animal care, aquarium shops, pets near me',
 'Find trusted pet shops, vets, grooming parlours, and boarding facilities in your city. Give your pet the best care with My City Info.'),

(NULL, 'Financial Services', 'financial-services', '🏦', 150,
 'Access trusted financial services including banks, insurance, loans, and investment advisory. Manage your finances with confidence.',
 'Financial Services Near You | My City Info',
 'banks near me, insurance agents, loan providers, investment advisors, stock brokers, microfinance, financial services, insurance',
 'Discover banks, insurance agents, loan providers, and financial advisors in your city. Manage your money smarter with My City Info.'),

(NULL, 'Repair & Maintenance', 'repair-maintenance', '🛠️', 160,
 'Get your gadgets, shoes, and electronics repaired by skilled local technicians. Quick, affordable, and reliable repair services at your doorstep.',
 'Repair & Maintenance Services Near You | My City Info',
 'mobile repair, laptop repair, electronics repair, watch repair, shoe repair, gadget repair, repair services, maintenance',
 'Find skilled repair technicians for mobiles, laptops, electronics, and more in your city. Fast and affordable repairs on My City Info.'),

(NULL, 'Manufacturing & Industrial', 'manufacturing-industrial', '🏭', 170,
 'Connect with local manufacturers, fabricators, and industrial service providers. Sourcing and production solutions for businesses of all sizes.',
 'Manufacturing & Industrial Services Near You | My City Info',
 'manufacturers, fabrication services, printing services, packaging, industrial equipment, small manufacturers, production, industrial services',
 'Find local manufacturers, fabrication units, and industrial service providers in your city. Source and produce with confidence on My City Info.'),

(NULL, 'Local Services', 'local-services', '🧺', 180,
 'Access everyday local services including tailors, laundry, printing, and more. Convenient services that make daily life easier in your city.',
 'Local Services Near You | My City Info',
 'tailors near me, laundry services, photocopy shops, internet cafes, recharge shops, locksmiths, local services, key makers',
 'Find handy local services like tailors, laundry, printing, and locksmiths in your city. Everyday convenience at your fingertips on My City Info.'),

(NULL, 'Religious & Community', 'religious-community', '🕌', 190,
 'Locate places of worship, community centres, and NGOs in your city. Connect with your community and find spiritual support close to home.',
 'Religious & Community Centres Near You | My City Info',
 'temples near me, mosques, churches, community centres, NGOs, religious places, prayer halls, community services',
 'Find temples, mosques, churches, community centres, and NGOs in your city. Stay connected to your community with My City Info.'),

(NULL, 'Government & Public Services', 'government-public-services', '🏛️', 200,
 'Locate government offices, police stations, post offices, and public utility services. Access essential civic services quickly and easily.',
 'Government & Public Services Near You | My City Info',
 'government offices, police stations, post offices, public utilities, civic services, municipal offices, government services',
 'Find government offices, police stations, post offices, and public services in your city. Access civic resources easily on My City Info.'),

(NULL, 'Agriculture & Farming', 'agriculture-farming', '🌿', 210,
 'Find suppliers, equipment dealers, and services for the agricultural and farming community. Supporting the backbone of our local economy.',
 'Agriculture & Farming Services Near You | My City Info',
 'fertilizer shops, seeds suppliers, farm equipment, dairy farms, agriculture services, farming supplies, agricultural dealers',
 'Discover fertilizer shops, seed suppliers, farm equipment dealers, and dairy farms in your city. Empowering local farmers on My City Info.'),

(NULL, 'Miscellaneous', 'miscellaneous', '🧩', 220,
 'A catch-all category for freelancers, home-based businesses, startups, and unique services that do not fit elsewhere. Every local business matters.',
 'Miscellaneous Local Businesses | My City Info',
 'freelancers, home-based businesses, startups, local businesses, unique services, independent professionals, small businesses',
 'Explore freelancers, startups, home-based businesses, and unique local services in your city. Every business has a place on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Home Services
-- ============================================================

SET @home_services_id = (SELECT id FROM mci_categories WHERE slug = 'home-services' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@home_services_id,
 'Plumbing', 'plumbing', '🔧', 10,
 'Find local plumbers for pipe repair, leak fixing, drainage, and bathroom fittings. Available for emergency and scheduled plumbing work.',
 'Plumbers Near You | My City Info',
 'plumber near me, plumbing services, pipe repair, leak fix, drainage, water supply, bathroom fittings, plumbing contractor',
 'Find trusted local plumbers for pipe repair, leaks, drainage, and bathroom fittings. Book a plumber quickly with My City Info.'),

(@home_services_id,
 'Electrical Services', 'electrical-services', '⚡', 20,
 'Connect with licensed electricians for wiring, panel upgrades, lighting installation, and emergency electrical repairs at home or office.',
 'Electricians Near You | My City Info',
 'electrician near me, electrical services, wiring, electrical repair, panel upgrade, lighting installation, electrical contractor',
 'Book trusted electricians in your city for wiring, repairs, and lighting installation. Fast and safe electrical services on My City Info.'),

(@home_services_id,
 'AC Repair & Installation', 'ac-repair-installation', '❄️', 30,
 'Get your air conditioner serviced, repaired, or installed by certified technicians. All brands and models covered.',
 'AC Repair & Installation Near You | My City Info',
 'AC repair near me, air conditioner service, AC installation, AC technician, split AC repair, window AC service, AC gas refill',
 'Find AC repair and installation experts in your city. Servicing all brands — get your AC running perfectly with My City Info.'),

(@home_services_id,
 'Appliance Repair', 'appliance-repair', '🔌', 40,
 'Get household appliances like washing machines, refrigerators, microwaves, and geysers repaired by certified technicians.',
 'Appliance Repair Services Near You | My City Info',
 'appliance repair near me, washing machine repair, refrigerator repair, microwave repair, geyser repair, home appliance service',
 'Find skilled appliance repair technicians in your city. Fast, reliable repairs for all home appliances on My City Info.'),

(@home_services_id,
 'Cleaning Services', 'cleaning-services', '🧹', 50,
 'Professional home, office, and deep cleaning services. Move-in/move-out cleaning, sofa cleaning, and post-renovation cleaning also available.',
 'Cleaning Services Near You | My City Info',
 'cleaning services near me, home cleaning, deep cleaning, office cleaning, sofa cleaning, bathroom cleaning, move-in cleaning',
 'Book professional cleaning services in your city. Home, office, and deep cleaning — find trusted cleaners on My City Info.'),

(@home_services_id,
 'Pest Control', 'pest-control', '🪲', 60,
 'Protect your home from pests with professional pest control treatments. Cockroach, termite, rodent, mosquito, and bed bug control services.',
 'Pest Control Services Near You | My City Info',
 'pest control near me, cockroach control, termite treatment, rodent control, mosquito control, bed bug treatment, pest extermination',
 'Find certified pest control services in your city. Effective treatments for all pests — book a safe extermination on My City Info.'),

(@home_services_id,
 'Carpentry', 'carpentry', '🪚', 70,
 'Find skilled carpenters for furniture making, repair, door and window fitting, and custom woodwork at home or office.',
 'Carpenters Near You | My City Info',
 'carpenter near me, carpentry services, furniture repair, wood work, door fitting, window fitting, custom furniture, cabinet making',
 'Book trusted carpenters in your city for furniture, doors, windows, and custom woodwork. Quality craftsmanship on My City Info.'),

(@home_services_id,
 'Painting Services', 'painting-services', '🎨', 80,
 'Hire professional painters for interior and exterior wall painting, texture work, waterproofing paint, and commercial painting projects.',
 'Painting Services Near You | My City Info',
 'painter near me, house painting, wall painting, interior painting, exterior painting, texture paint, painting contractor',
 'Find experienced painters in your city for interior, exterior, and texture painting. Transform your space with My City Info.'),

(@home_services_id,
 'Home Renovation', 'home-renovation', '🏚️', 90,
 'Complete home renovation and remodelling services including flooring, tiling, false ceilings, and full room makeovers.',
 'Home Renovation Services Near You | My City Info',
 'home renovation near me, house remodelling, flooring, tiling, false ceiling, room makeover, renovation contractor, home improvement',
 'Discover top home renovation contractors in your city. Flooring, tiling, remodelling, and full renovations on My City Info.'),

(@home_services_id,
 'Interior Design', 'interior-design', '🛋️', 100,
 'Work with creative interior designers to transform your living space. Residential and commercial interior design services available.',
 'Interior Designers Near You | My City Info',
 'interior designer near me, interior design, home decor, office interior, residential interior, commercial interior, space planning',
 'Find talented interior designers in your city for homes and offices. Beautiful, functional spaces designed on My City Info.'),

(@home_services_id,
 'Modular Kitchen', 'modular-kitchen', '🍳', 110,
 'Design and install your dream modular kitchen with top local manufacturers and suppliers. Custom sizes, materials, and finishes available.',
 'Modular Kitchen Services Near You | My City Info',
 'modular kitchen near me, kitchen design, kitchen cabinets, kitchen renovation, modular kitchen manufacturer, kitchen fitting',
 'Find modular kitchen designers and manufacturers in your city. Custom kitchens designed and installed — explore on My City Info.'),

(@home_services_id,
 'Waterproofing', 'waterproofing', '💧', 120,
 'Protect your home from water seepage, leakage, and dampness with professional waterproofing services for terraces, bathrooms, and basements.',
 'Waterproofing Services Near You | My City Info',
 'waterproofing near me, water leakage repair, terrace waterproofing, bathroom waterproofing, seepage repair, damp proofing',
 'Find waterproofing experts in your city. Terrace, bathroom, and basement waterproofing solutions — book now on My City Info.'),

(@home_services_id,
 'CCTV Installation', 'cctv-installation', '📷', 130,
 'Install CCTV cameras and security systems at your home or business for round-the-clock surveillance and peace of mind.',
 'CCTV Installation Services Near You | My City Info',
 'CCTV installation near me, security camera, home security system, CCTV setup, IP camera, surveillance system, security installation',
 'Find CCTV and security camera installation experts in your city. Protect your home and business with My City Info.'),

(@home_services_id,
 'Solar Panel Installation', 'solar-panel-installation', '☀️', 140,
 'Go green with solar panel installation for homes and businesses. Reduce electricity bills and get government subsidy guidance.',
 'Solar Panel Installation Near You | My City Info',
 'solar panel installation near me, solar energy, rooftop solar, solar power system, solar inverter, green energy, solar subsidy',
 'Find solar panel installation experts in your city. Go green and cut electricity costs — get started with My City Info.');


-- ============================================================
-- SUBCATEGORIES — Health & Medical
-- ============================================================

SET @health_medical_id = (SELECT id FROM mci_categories WHERE slug = 'health-medical' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@health_medical_id,
 'General Physicians', 'general-physicians', '👨‍⚕️', 10,
 'Consult experienced general physicians for common illnesses, routine check-ups, and health advice. Available for home visits and clinic appointments.',
 'General Physicians Near You | My City Info',
 'general physician near me, GP doctor, family doctor, doctor consultation, medical check-up, home visit doctor, primary care doctor',
 'Find trusted general physicians in your city for consultations, check-ups, and home visits. Your primary healthcare on My City Info.'),

(@health_medical_id,
 'Clinics', 'clinics', '🏥', 20,
 'Locate multi-specialty and single-specialty clinics for outpatient consultations, minor procedures, and preventive care near you.',
 'Clinics Near You | My City Info',
 'clinics near me, medical clinic, outpatient clinic, specialist clinic, health clinic, walk-in clinic, multi-specialty clinic',
 'Find medical clinics in your city for outpatient care, specialist consultations, and minor procedures on My City Info.'),

(@health_medical_id,
 'Hospitals', 'hospitals', '🏨', 30,
 'Find accredited hospitals for emergency care, surgeries, maternity, and advanced medical treatments in your city.',
 'Hospitals Near You | My City Info',
 'hospitals near me, emergency hospital, multi-specialty hospital, private hospital, government hospital, maternity hospital',
 'Locate top hospitals in your city for emergency care, surgeries, and advanced treatments. Your health is our priority on My City Info.'),

(@health_medical_id,
 'Dentists', 'dentists', '🦷', 40,
 'Find qualified dentists for tooth extraction, root canal, braces, teeth whitening, dental implants, and routine check-ups.',
 'Dentists Near You | My City Info',
 'dentist near me, dental clinic, tooth extraction, root canal, braces, teeth whitening, dental implant, orthodontist',
 'Book experienced dentists in your city for all dental treatments. From check-ups to implants — find dental care on My City Info.'),

(@health_medical_id,
 'Physiotherapy', 'physiotherapy', '🏃', 50,
 'Get relief from pain, injuries, and movement disorders with professional physiotherapy and rehabilitation services near you.',
 'Physiotherapy Centres Near You | My City Info',
 'physiotherapy near me, physio clinic, rehabilitation, sports injury, back pain treatment, joint pain, physiotherapist, rehab centre',
 'Find physiotherapy centres in your city for injury recovery, pain relief, and rehabilitation. Book a physio on My City Info.'),

(@health_medical_id,
 'Diagnostic Labs', 'diagnostic-labs', '🧪', 60,
 'Get blood tests, urine tests, X-rays, MRI, CT scans, and other diagnostic tests from accredited labs with home sample collection.',
 'Diagnostic Labs Near You | My City Info',
 'diagnostic lab near me, blood test, pathology lab, X-ray centre, MRI scan, CT scan, home sample collection, medical testing',
 'Find accredited diagnostic labs in your city for blood tests, scans, and pathology. Home collection available — book on My City Info.'),

(@health_medical_id,
 'Eye Care', 'eye-care', '👁️', 70,
 'Visit ophthalmologists and optometrists for eye tests, spectacles, contact lenses, cataract surgery, and laser eye treatments.',
 'Eye Care Specialists Near You | My City Info',
 'eye doctor near me, ophthalmologist, optometrist, eye test, spectacles, contact lenses, cataract surgery, laser eye surgery',
 'Find eye care specialists in your city for eye tests, glasses, contact lenses, and surgery. See clearly with My City Info.'),

(@health_medical_id,
 'Skin Care / Dermatology', 'skin-care-dermatology', '✨', 80,
 'Consult dermatologists for acne, eczema, psoriasis, hair loss, skin infections, and cosmetic skin treatments near you.',
 'Dermatologists Near You | My City Info',
 'dermatologist near me, skin doctor, acne treatment, eczema, psoriasis, hair loss treatment, skin clinic, cosmetic dermatology',
 'Book dermatologists in your city for skin, hair, and nail conditions. Expert cosmetic and medical skin care on My City Info.'),

(@health_medical_id,
 'Mental Health / Psychologists', 'mental-health-psychologists', '🧠', 90,
 'Access confidential counselling, therapy, and psychological support for anxiety, depression, stress, and relationship issues.',
 'Mental Health Services Near You | My City Info',
 'psychologist near me, mental health counselling, therapist, anxiety treatment, depression help, counselling services, psychiatrist',
 'Find mental health professionals in your city for counselling and therapy. Confidential, compassionate care on My City Info.'),

(@health_medical_id,
 'Ayurveda / Homeopathy', 'ayurveda-homeopathy', '🌿', 100,
 'Explore natural healing with experienced Ayurvedic doctors and homeopathic practitioners for chronic conditions and holistic wellness.',
 'Ayurveda & Homeopathy Near You | My City Info',
 'ayurveda near me, homeopathy doctor, Ayurvedic clinic, panchakarma, herbal treatment, natural medicine, holistic health',
 'Find Ayurvedic and homeopathic practitioners in your city for natural, holistic healing. Traditional medicine on My City Info.'),

(@health_medical_id,
 'Pharmacies', 'pharmacies', '💊', 110,
 'Locate nearby pharmacies and medical stores for prescription medicines, OTC drugs, and health supplements. Some offer home delivery.',
 'Pharmacies Near You | My City Info',
 'pharmacy near me, medical store, chemist, medicine delivery, prescription drugs, OTC medicines, health supplements, drug store',
 'Find pharmacies and medical stores in your city. Buy medicines, supplements, and healthcare products on My City Info.'),

(@health_medical_id,
 'Nursing Services', 'nursing-services', '👩‍⚕️', 120,
 'Hire trained nurses for at-home patient care, post-surgery recovery, elderly care, and wound dressing services.',
 'Nursing Services Near You | My City Info',
 'nursing services near me, home nurse, patient care, elderly care, post-surgery care, wound dressing, caregiver, nurse at home',
 'Find professional nursing and home care services in your city. Compassionate patient care at home on My City Info.'),

(@health_medical_id,
 'Ambulance Services', 'ambulance-services', '🚑', 130,
 'Quick and reliable ambulance and emergency medical transport services available 24/7 across the city.',
 'Ambulance Services Near You | My City Info',
 'ambulance near me, emergency ambulance, medical transport, patient transport, 24/7 ambulance, ICU ambulance',
 'Find 24/7 ambulance and emergency medical transport services in your city. Quick response when it matters most on My City Info.'),

(@health_medical_id,
 'Blood Banks', 'blood-banks', '🩸', 140,
 'Find registered blood banks for voluntary blood donation, blood component supply, and emergency blood requirements in your city.',
 'Blood Banks Near You | My City Info',
 'blood bank near me, blood donation, blood supply, voluntary blood donation, blood components, platelet donation, emergency blood',
 'Locate blood banks in your city for donations and emergency blood supply. Find blood banks quickly on My City Info.'),

(@health_medical_id,
 'Speech Therapy', 'speech-therapy', '🗣️', 150,
 'Connect with certified speech therapists for children and adults facing speech, language, fluency, and swallowing disorders.',
 'Speech Therapy Services Near You | My City Info',
 'speech therapist near me, speech therapy, language disorder, stuttering treatment, child speech delay, voice therapy, communication therapy',
 'Find certified speech therapists in your city for all ages. Effective treatment for speech and language disorders on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Beauty & Personal Care
-- ============================================================

SET @beauty_personal_care_id = (SELECT id FROM mci_categories WHERE slug = 'beauty-personal-care' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@beauty_personal_care_id,
 'Salons', 'salons', '💇', 10,
 'Book appointments at top hair salons and beauty parlours for haircuts, styling, colouring, facials, and more.',
 'Salons Near You | My City Info',
 'salon near me, hair salon, beauty parlour, haircut, hair colouring, facial, threading, waxing, women salon, men salon',
 'Discover top salons and beauty parlours in your city for hair, skin, and beauty services. Book an appointment on My City Info.'),

(@beauty_personal_care_id,
 'Spas', 'spas', '🧖', 20,
 'Indulge in relaxing spa treatments including body massages, scrubs, aromatherapy, and rejuvenation therapies.',
 'Spas Near You | My City Info',
 'spa near me, massage centre, body massage, aromatherapy, relaxation, spa treatments, couple spa, luxury spa, day spa',
 'Discover the best spas in your city for massages, scrubs, and rejuvenation therapies. Relax and unwind with My City Info.'),

(@beauty_personal_care_id,
 'Makeup Artists', 'makeup-artists', '💋', 30,
 'Hire professional makeup artists for weddings, parties, photoshoots, and special occasions. Experienced with all skin tones.',
 'Makeup Artists Near You | My City Info',
 'makeup artist near me, bridal makeup, party makeup, professional makeup, airbrush makeup, HD makeup, photoshoot makeup',
 'Book talented makeup artists in your city for weddings, parties, and events. Flawless looks for every occasion on My City Info.'),

(@beauty_personal_care_id,
 'Bridal Services', 'bridal-services', '👰', 40,
 'Complete bridal beauty packages including makeup, hairstyling, mehndi, and pre-bridal skin treatments for your special day.',
 'Bridal Services Near You | My City Info',
 'bridal services near me, bridal makeup, bridal package, mehndi artist, pre-bridal treatment, wedding beauty, bridal hairstyle',
 'Find complete bridal beauty services in your city. Makeup, hairstyling, mehndi, and more — book your bridal package on My City Info.'),

(@beauty_personal_care_id,
 'Tattoo Studios', 'tattoo-studios', '🖊️', 50,
 'Get custom tattoos and body art from skilled tattoo artists. Temporary tattoos and piercing services also available.',
 'Tattoo Studios Near You | My City Info',
 'tattoo studio near me, tattoo artist, custom tattoo, temporary tattoo, body piercing, tattoo design, permanent tattoo',
 'Find the best tattoo studios in your city. Custom designs by talented artists — discover tattoo services on My City Info.'),

(@beauty_personal_care_id,
 'Nail Art Studios', 'nail-art-studios', '💅', 60,
 'Get stunning nail art, manicures, pedicures, gel nails, and nail extensions at professional nail studios.',
 'Nail Art Studios Near You | My City Info',
 'nail art near me, nail studio, manicure, pedicure, gel nails, nail extensions, nail design, acrylic nails',
 'Discover nail art studios in your city for manicures, pedicures, gel nails, and creative nail designs on My City Info.'),

(@beauty_personal_care_id,
 'Hair Treatment Clinics', 'hair-treatment-clinics', '💈', 70,
 'Address hair loss, dandruff, and scalp issues with professional hair treatment clinics offering PRP, keratin, and hair transplant services.',
 'Hair Treatment Clinics Near You | My City Info',
 'hair treatment near me, hair loss treatment, PRP hair, keratin treatment, hair transplant, hair clinic, scalp treatment, hair care',
 'Find hair treatment clinics in your city for hair loss, PRP, keratin, and transplant services. Restore your hair on My City Info.'),

(@beauty_personal_care_id,
 'Skincare Clinics', 'skincare-clinics', '🌟', 80,
 'Visit expert skincare clinics for advanced treatments like chemical peels, laser skin care, anti-ageing, and acne scar removal.',
 'Skincare Clinics Near You | My City Info',
 'skincare clinic near me, skin treatment, chemical peel, laser skin care, anti-ageing, acne scar removal, skin whitening',
 'Find skincare clinics in your city for advanced skin treatments and cosmetic procedures. Glow up with My City Info.'),

(@beauty_personal_care_id,
 'Wellness Centers', 'wellness-centers', '🧘', 90,
 'Holistic wellness centres offering meditation, yoga, naturopathy, detox programmes, and stress management for complete wellbeing.',
 'Wellness Centers Near You | My City Info',
 'wellness centre near me, holistic wellness, meditation centre, naturopathy, detox programme, stress management, yoga wellness',
 'Find wellness centres in your city for holistic health, meditation, naturopathy, and detox programmes on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Food & Restaurants
-- ============================================================

SET @food_restaurants_id = (SELECT id FROM mci_categories WHERE slug = 'food-restaurants' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@food_restaurants_id,
 'Restaurants', 'restaurants', '🍽️', 10,
 'Discover the best restaurants for dine-in experiences across all cuisines — Indian, Chinese, Continental, and more.',
 'Restaurants Near You | My City Info',
 'restaurants near me, best restaurants, dine-in, Indian restaurant, Chinese restaurant, multi-cuisine, family restaurant',
 'Find the best restaurants in your city for every cuisine and occasion. Dine-in experiences you will love — on My City Info.'),

(@food_restaurants_id,
 'Cafes', 'cafes', '☕', 20,
 'Find cosy cafes for coffee, tea, snacks, and light bites. Great places to work, meet friends, or simply relax.',
 'Cafes Near You | My City Info',
 'cafe near me, coffee shop, tea cafe, best cafes, work cafe, cafe for students, brunch cafe, cold coffee, cafe with wifi',
 'Discover the best cafes in your city for coffee, snacks, and a great ambiance. Find your perfect cafe on My City Info.'),

(@food_restaurants_id,
 'Bakeries', 'bakeries', '🥐', 30,
 'Fresh bread, cakes, pastries, and confectionery from local bakeries. Custom cake orders for birthdays and celebrations also available.',
 'Bakeries Near You | My City Info',
 'bakery near me, cake shop, fresh bread, pastry shop, custom cakes, birthday cakes, confectionery, artisan bakery',
 'Find the best bakeries in your city for fresh bread, cakes, and pastries. Custom celebration cakes available on My City Info.'),

(@food_restaurants_id,
 'Fast Food', 'fast-food', '🍔', 40,
 'Quick bites and fast food joints for burgers, pizzas, sandwiches, momos, and more. Perfect for on-the-go meals.',
 'Fast Food Near You | My City Info',
 'fast food near me, burger shop, pizza place, sandwich, momos, quick bites, takeaway, fast food restaurant',
 'Find fast food joints in your city for burgers, pizza, momos, and quick bites. Grab a quick meal with My City Info.'),

(@food_restaurants_id,
 'Fine Dining', 'fine-dining', '🥂', 50,
 'Experience premium fine dining restaurants with curated menus, elegant ambiance, and exceptional service for special occasions.',
 'Fine Dining Restaurants Near You | My City Info',
 'fine dining near me, luxury restaurant, premium dining, special occasion restaurant, gourmet food, rooftop dining, fine cuisine',
 'Discover fine dining restaurants in your city for special occasions. Exquisite food and ambiance — book a table on My City Info.'),

(@food_restaurants_id,
 'Street Food Vendors', 'street-food-vendors', '🌮', 60,
 'Explore popular street food stalls and vendors serving local favourites like pani puri, chaat, vada pav, dosa, and more.',
 'Street Food Near You | My City Info',
 'street food near me, pani puri, chaat, vada pav, dosa stall, local food stalls, street food vendors, food carts',
 'Find the best street food vendors in your city. Authentic, affordable, and delicious local street eats on My City Info.'),

(@food_restaurants_id,
 'Catering Services', 'catering-services', '🍱', 70,
 'Book professional catering services for weddings, corporate events, birthday parties, and small gatherings.',
 'Catering Services Near You | My City Info',
 'catering services near me, wedding caterer, corporate catering, event catering, party catering, food catering, buffet services',
 'Find trusted catering services in your city for weddings, events, and parties. Book a caterer easily on My City Info.'),

(@food_restaurants_id,
 'Cloud Kitchens', 'cloud-kitchens', '📦', 80,
 'Order delicious food from delivery-only cloud kitchens specialising in specific cuisines, dietary menus, and quick delivery.',
 'Cloud Kitchens Near You | My City Info',
 'cloud kitchen near me, food delivery, delivery only kitchen, online food order, home delivery food, dark kitchen',
 'Discover cloud kitchens in your city for fast food delivery. Order from specialty kitchens on My City Info.'),

(@food_restaurants_id,
 'Sweet Shops', 'sweet-shops', '🍬', 90,
 'Find local mithai shops and sweet stores for traditional Indian sweets, dry fruits, and festive gifting.',
 'Sweet Shops Near You | My City Info',
 'sweet shop near me, mithai shop, Indian sweets, dry fruit shop, festive sweets, halwai, barfi, gulab jamun, ladoo',
 'Discover traditional sweet shops in your city for mithai, dry fruits, and festive treats. Sweetness delivered on My City Info.'),

(@food_restaurants_id,
 'Juice & Beverage Shops', 'juice-beverage-shops', '🥤', 100,
 'Fresh fruit juices, smoothies, milkshakes, lassi, coconut water, and health beverages from local juice bars and shops.',
 'Juice & Beverage Shops Near You | My City Info',
 'juice shop near me, fresh juice, smoothie bar, milkshake, lassi, coconut water, health drinks, fruit juice, cold press juice',
 'Find juice and beverage shops in your city for fresh juices, smoothies, and health drinks. Refresh yourself with My City Info.'),

(@food_restaurants_id,
 'Tiffin Services', 'tiffin-services', '🥘', 110,
 'Get homely, nutritious tiffin meals delivered to your doorstep daily. Ideal for working professionals, students, and PG residents.',
 'Tiffin Services Near You | My City Info',
 'tiffin service near me, home food delivery, dabba service, lunch box delivery, daily meals, homemade food delivery, meal subscription',
 'Find reliable tiffin services in your city for home-cooked meals delivered daily. Healthy, affordable food on My City Info.'),

(@food_restaurants_id,
 'Dhaba', 'dhaba', '🍛', 120,
 'Authentic highway and local dhabas serving wholesome North Indian food, dal makhani, roti, and traditional Indian flavours.',
 'Dhabas Near You | My City Info',
 'dhaba near me, local dhaba, North Indian dhaba, highway dhaba, dal makhani, traditional food, rustic dining, authentic dhaba',
 'Find the best dhabas in your city for authentic and wholesome North Indian food. Real flavours, great value on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Shopping & Retail
-- ============================================================

SET @shopping_retail_id = (SELECT id FROM mci_categories WHERE slug = 'shopping-retail' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@shopping_retail_id,
 'Clothing Stores', 'clothing-stores', '👗', 10,
 'Shop for ethnic wear, western wear, kids clothing, sportswear, and casual outfits at local clothing stores and boutiques.',
 'Clothing Stores Near You | My City Info',
 'clothing store near me, clothes shop, ethnic wear, western wear, boutique, kids clothing, fashion store, saree shop, kurti shop',
 'Find clothing stores and boutiques in your city for all styles and occasions. Fashion for everyone on My City Info.'),

(@shopping_retail_id,
 'Footwear Stores', 'footwear-stores', '👟', 20,
 'Find shoes, sandals, boots, and sports footwear at local footwear stores and branded outlets.',
 'Footwear Stores Near You | My City Info',
 'shoe shop near me, footwear store, sandals, sports shoes, ladies footwear, mens shoes, kids shoes, branded footwear',
 'Discover footwear stores in your city for shoes, sandals, and boots for all occasions. Walk in style with My City Info.'),

(@shopping_retail_id,
 'Electronics Stores', 'electronics-stores', '📺', 30,
 'Shop for TVs, refrigerators, washing machines, air conditioners, and other electronics at local electronics stores.',
 'Electronics Stores Near You | My City Info',
 'electronics store near me, TV shop, home appliances, refrigerator, washing machine, electronics dealer, consumer electronics',
 'Find electronics stores in your city for TVs, home appliances, and gadgets. Shop local electronics on My City Info.'),

(@shopping_retail_id,
 'Mobile Shops', 'mobile-shops', '📱', 40,
 'Buy smartphones, tablets, mobile accessories, and prepaid SIM cards at local mobile shops and authorised service centres.',
 'Mobile Shops Near You | My City Info',
 'mobile shop near me, smartphone store, phone shop, mobile accessories, SIM card, tablet shop, mobile dealer, phone accessories',
 'Find mobile shops in your city for smartphones, tablets, and accessories. Compare deals and buy local on My City Info.'),

(@shopping_retail_id,
 'Grocery Stores', 'grocery-stores', '🛒', 50,
 'Shop for daily groceries, fresh vegetables, fruits, dairy, and staples at local grocery stores and kirana shops.',
 'Grocery Stores Near You | My City Info',
 'grocery store near me, kirana shop, vegetables, fruits, dairy, staples, daily essentials, local grocery, superstore',
 'Find grocery stores in your city for daily essentials, fresh produce, and staples. Shop local groceries on My City Info.'),

(@shopping_retail_id,
 'Supermarkets', 'supermarkets', '🏪', 60,
 'One-stop supermarkets for groceries, household products, personal care, and ready-to-cook meals. All under one roof.',
 'Supermarkets Near You | My City Info',
 'supermarket near me, hypermarket, departmental store, grocery chain, household products, one stop shopping',
 'Find supermarkets in your city for all-in-one grocery and household shopping. Browse local supermarkets on My City Info.'),

(@shopping_retail_id,
 'Furniture Stores', 'furniture-stores', '🛋️', 70,
 'Furnish your home with quality sofas, beds, wardrobes, dining sets, and office furniture from local furniture stores.',
 'Furniture Stores Near You | My City Info',
 'furniture store near me, sofa shop, bed shop, wardrobe, dining table, office furniture, home furniture, furniture showroom',
 'Find furniture stores in your city for home and office furnishings. Quality furniture at great prices on My City Info.'),

(@shopping_retail_id,
 'Gift Shops', 'gift-shops', '🎁', 80,
 'Find unique gifts, hampers, personalised items, and greeting cards at local gift shops for all occasions.',
 'Gift Shops Near You | My City Info',
 'gift shop near me, personalised gifts, gift hampers, greeting cards, birthday gifts, anniversary gifts, customised gifts',
 'Find unique gift shops in your city for birthdays, anniversaries, and special occasions. Thoughtful gifts on My City Info.'),

(@shopping_retail_id,
 'Bookstores', 'bookstores', '📚', 90,
 'Browse fiction, non-fiction, academic, and children''s books at local bookstores and second-hand book shops.',
 'Bookstores Near You | My City Info',
 'bookstore near me, book shop, second hand books, academic books, novels, children books, stationery and books',
 'Find bookstores in your city for all genres and ages. New, second-hand, and academic books available on My City Info.'),

(@shopping_retail_id,
 'Jewelry Stores', 'jewelry-stores', '💎', 100,
 'Explore gold, silver, diamond jewellery and imitation jewellery at trusted local jewellers and showrooms.',
 'Jewelry Stores Near You | My City Info',
 'jewellery store near me, gold jewellery, diamond jewellery, silver jewellery, imitation jewellery, jeweller, bridal jewellery',
 'Discover jewellery stores in your city for gold, silver, diamond, and bridal jewellery. Shop fine jewellery on My City Info.'),

(@shopping_retail_id,
 'Hardware Stores', 'hardware-stores', '🔨', 110,
 'Find hardware stores for tools, fasteners, construction materials, paints, and plumbing supplies for DIY and professional use.',
 'Hardware Stores Near You | My City Info',
 'hardware store near me, tools shop, construction materials, paints, plumbing supplies, fasteners, building materials',
 'Find hardware stores in your city for tools, materials, and building supplies. Everything you need on My City Info.'),

(@shopping_retail_id,
 'Optical Stores', 'optical-stores', '👓', 120,
 'Get prescription eyeglasses, sunglasses, contact lenses, and eye tests at local optical stores and opticians.',
 'Optical Stores Near You | My City Info',
 'optical store near me, eyeglasses, sunglasses, contact lenses, optician, spectacle shop, prescription glasses, eye test',
 'Find optical stores in your city for glasses, sunglasses, and contact lenses. Clear vision with My City Info.'),

(@shopping_retail_id,
 'Toy Stores', 'toy-stores', '🧸', 130,
 'Shop for kids'' toys, board games, educational toys, and baby products at local toy stores and children''s shops.',
 'Toy Stores Near You | My City Info',
 'toy store near me, kids toys, board games, educational toys, baby products, remote control toys, outdoor toys, toy shop',
 'Find toy stores in your city for kids of all ages. Educational and fun toys for every child on My City Info.'),

(@shopping_retail_id,
 'Stationery Stores', 'stationery-stores', '✏️', 140,
 'Shop for office stationery, school supplies, art materials, and craft items at local stationery and bookshops.',
 'Stationery Stores Near You | My City Info',
 'stationery store near me, office supplies, school stationery, art supplies, craft materials, notebooks, pens, stationery shop',
 'Find stationery stores in your city for school, office, and art supplies. Everything you need to write and create on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Automotive
-- ============================================================

SET @automotive_id = (SELECT id FROM mci_categories WHERE slug = 'automotive' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@automotive_id,
 'Car Repair', 'car-repair', '🔧', 10,
 'Find trusted car mechanics and service centres for all car repairs, servicing, denting, painting, and engine work.',
 'Car Repair Near You | My City Info',
 'car repair near me, car mechanic, car service, denting painting, engine repair, car garage, automobile repair, car workshop',
 'Find trusted car repair garages in your city for servicing, denting, painting, and engine work on My City Info.'),

(@automotive_id,
 'Bike Repair', 'bike-repair', '🏍️', 20,
 'Get your motorcycle or scooter serviced, repaired, and maintained at reliable local bike repair shops.',
 'Bike Repair Near You | My City Info',
 'bike repair near me, motorcycle repair, scooter service, two-wheeler repair, bike mechanic, bike service centre',
 'Find bike repair shops in your city for motorcycle and scooter servicing and repairs on My City Info.'),

(@automotive_id,
 'Car Wash', 'car-wash', '🚿', 30,
 'Get your car cleaned inside and out at professional car wash centres offering exterior wash, interior detailing, and polishing.',
 'Car Wash Services Near You | My City Info',
 'car wash near me, car detailing, car cleaning, interior cleaning, car polish, steam car wash, car grooming',
 'Find car wash and detailing centres in your city. Keep your car spotless with professional cleaning on My City Info.'),

(@automotive_id,
 'Car Rentals', 'car-rentals', '🚙', 40,
 'Rent self-drive or chauffeur-driven cars for local city travel, outstation trips, and airport transfers.',
 'Car Rentals Near You | My City Info',
 'car rental near me, self drive car, cab hire, chauffeur car, outstation car rental, airport cab, car hire, vehicle rental',
 'Find car rental services in your city for self-drive and chauffeur options. Rent a car easily on My City Info.'),

(@automotive_id,
 'Bike Rentals', 'bike-rentals', '🛵', 50,
 'Rent motorcycles, scooters, and bicycles for city commutes, travel, or tourism at affordable daily or hourly rates.',
 'Bike Rentals Near You | My City Info',
 'bike rental near me, scooter rental, motorcycle hire, two-wheeler rental, cycle rental, bike on rent, scooty rental',
 'Find bike and scooter rentals in your city for commuting or exploring. Affordable two-wheeler hire on My City Info.'),

(@automotive_id,
 'Auto Dealers', 'auto-dealers', '🏪', 60,
 'Buy new and used cars, bikes, and commercial vehicles from authorised dealers and certified pre-owned outlets.',
 'Auto Dealers Near You | My City Info',
 'car dealer near me, used car, new car showroom, bike dealer, authorised dealer, second hand car, pre-owned vehicles',
 'Find new and used vehicle dealers in your city. Buy your next car or bike confidently with My City Info.'),

(@automotive_id,
 'Spare Parts', 'spare-parts', '⚙️', 70,
 'Source genuine and aftermarket spare parts for cars, bikes, and commercial vehicles at local auto parts stores.',
 'Auto Spare Parts Near You | My City Info',
 'spare parts near me, auto parts, car spare parts, bike spare parts, genuine parts, aftermarket parts, vehicle parts store',
 'Find auto spare parts stores in your city for genuine and aftermarket parts for all vehicles on My City Info.'),

(@automotive_id,
 'Towing Services', 'towing-services', '🚛', 80,
 '24/7 vehicle towing and roadside assistance services for breakdowns, accidents, and emergency vehicle recovery.',
 'Towing Services Near You | My City Info',
 'towing service near me, roadside assistance, car towing, vehicle recovery, breakdown service, accident towing, 24 hour towing',
 'Find 24/7 towing and roadside assistance in your city for breakdowns and emergencies on My City Info.'),

(@automotive_id,
 'Driving Schools', 'driving-schools', '🚦', 90,
 'Learn to drive cars, bikes, and heavy vehicles at certified driving schools offering beginner to advanced courses.',
 'Driving Schools Near You | My City Info',
 'driving school near me, learn to drive, driving lessons, driving instructor, car driving class, license training, driving course',
 'Find certified driving schools in your city for cars, bikes, and heavy vehicles. Get your licence with My City Info.'),

(@automotive_id,
 'EV Charging Stations', 'ev-charging-stations', '🔋', 100,
 'Locate electric vehicle charging stations across the city for two-wheelers, four-wheelers, and commercial EVs.',
 'EV Charging Stations Near You | My City Info',
 'EV charging station near me, electric vehicle charging, EV charger, fast charging, Type 2 charger, CCS charger, EV station',
 'Find EV charging stations in your city for all electric vehicles. Power up your EV conveniently with My City Info.');


-- ============================================================
-- SUBCATEGORIES — Professional Services
-- ============================================================

SET @professional_services_id = (SELECT id FROM mci_categories WHERE slug = 'professional-services' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@professional_services_id,
 'Chartered Accountants', 'chartered-accountants', '📊', 10,
 'Hire experienced CAs for income tax filing, GST compliance, auditing, company registration, and financial advisory.',
 'Chartered Accountants Near You | My City Info',
 'chartered accountant near me, CA firm, income tax filing, GST return, audit, company registration, financial advisory, tax planning',
 'Find experienced chartered accountants in your city for tax, GST, audit, and compliance services on My City Info.'),

(@professional_services_id,
 'Lawyers', 'lawyers', '⚖️', 20,
 'Consult experienced lawyers and advocates for civil, criminal, property, family, and corporate legal matters.',
 'Lawyers Near You | My City Info',
 'lawyer near me, advocate, legal advice, civil lawyer, criminal lawyer, property lawyer, family law, corporate lawyer',
 'Find trusted lawyers and advocates in your city for all legal matters. Get expert legal counsel on My City Info.'),

(@professional_services_id,
 'Consultants', 'consultants', '💼', 30,
 'Hire business, management, strategy, and operations consultants to help your organisation solve complex challenges.',
 'Consultants Near You | My City Info',
 'business consultant near me, management consultant, strategy consultant, operations consultant, startup advisor, business advisor',
 'Find experienced business and management consultants in your city. Strategic advice for growth on My City Info.'),

(@professional_services_id,
 'Digital Marketing Agencies', 'digital-marketing-agencies', '📣', 40,
 'Grow your business online with digital marketing agencies offering SEO, social media marketing, PPC, and content marketing.',
 'Digital Marketing Agencies Near You | My City Info',
 'digital marketing agency near me, SEO agency, social media marketing, PPC, content marketing, online marketing, growth marketing',
 'Find digital marketing agencies in your city for SEO, social media, and online growth. Scale your business with My City Info.'),

(@professional_services_id,
 'IT Services', 'it-services', '💻', 50,
 'Hire IT service providers for network setup, server management, cybersecurity, cloud solutions, and IT support.',
 'IT Services Near You | My City Info',
 'IT services near me, network setup, server management, cybersecurity, cloud services, IT support, managed IT, tech support',
 'Find IT service providers in your city for network, cloud, security, and support solutions on My City Info.'),

(@professional_services_id,
 'Web Development', 'web-development', '🌐', 60,
 'Get professional websites, e-commerce platforms, and web applications built by experienced local web developers.',
 'Web Development Services Near You | My City Info',
 'web developer near me, website design, e-commerce website, web application, WordPress developer, web agency, website development',
 'Find web development agencies in your city for websites, apps, and e-commerce. Build your online presence on My City Info.'),

(@professional_services_id,
 'Graphic Design', 'graphic-design', '🎨', 70,
 'Hire graphic designers for logos, branding, brochures, packaging, social media creatives, and marketing materials.',
 'Graphic Design Services Near You | My City Info',
 'graphic designer near me, logo design, branding, brochure design, social media design, packaging design, creative design',
 'Find graphic designers in your city for logos, branding, and marketing materials. Bring your vision to life on My City Info.'),

(@professional_services_id,
 'Business Consultants', 'business-consultants', '📈', 80,
 'Get expert guidance on business setup, expansion, funding, operations, and compliance from experienced business consultants.',
 'Business Consultants Near You | My City Info',
 'business consultant near me, startup consultant, business advisory, business setup, expansion planning, funding advisor',
 'Find business consultants in your city for setup, growth, and funding guidance. Expert advice for your business on My City Info.'),

(@professional_services_id,
 'HR Services', 'hr-services', '👥', 90,
 'Outsource recruitment, payroll processing, HR compliance, and employee management to professional HR service firms.',
 'HR Services Near You | My City Info',
 'HR services near me, recruitment agency, payroll services, HR compliance, staffing agency, HR outsourcing, hiring solutions',
 'Find HR and staffing services in your city for recruitment, payroll, and compliance. Build your team with My City Info.'),

(@professional_services_id,
 'Tax Consultants', 'tax-consultants', '🧾', 100,
 'Hire tax consultants for personal and business tax planning, ITR filing, GST registration, and tax advisory services.',
 'Tax Consultants Near You | My City Info',
 'tax consultant near me, ITR filing, tax planning, GST consultant, income tax advisor, tax return, tax advisory',
 'Find tax consultants in your city for ITR filing, GST, and tax planning. Stay compliant and save on taxes with My City Info.');


-- ============================================================
-- SUBCATEGORIES — Education & Training
-- ============================================================

SET @education_training_id = (SELECT id FROM mci_categories WHERE slug = 'education-training' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@education_training_id,
 'Schools', 'schools', '🏫', 10,
 'Find CBSE, ICSE, State Board, and international schools for primary, middle, and high school education near you.',
 'Schools Near You | My City Info',
 'schools near me, CBSE school, ICSE school, primary school, high school, best school, international school, admission',
 'Find the best schools in your city for CBSE, ICSE, and international boards. Admissions and school info on My City Info.'),

(@education_training_id,
 'Colleges', 'colleges', '🎓', 20,
 'Explore undergraduate and postgraduate colleges for engineering, medicine, arts, commerce, and science streams.',
 'Colleges Near You | My City Info',
 'college near me, engineering college, medical college, arts college, commerce college, degree college, admission, university',
 'Find colleges in your city for engineering, medicine, arts, and more. Explore admission options on My City Info.'),

(@education_training_id,
 'Coaching Classes', 'coaching-classes', '📖', 30,
 'Join coaching institutes for JEE, NEET, UPSC, CA, and other competitive exam preparation with experienced faculty.',
 'Coaching Classes Near You | My City Info',
 'coaching classes near me, JEE coaching, NEET coaching, UPSC coaching, CA coaching, competitive exam, entrance exam preparation',
 'Find coaching institutes in your city for JEE, NEET, UPSC, and competitive exams. Ace your exams with My City Info.'),

(@education_training_id,
 'Tuition Centers', 'tuition-centers', '✏️', 40,
 'Find local tuition centres and home tutors for school subjects, including maths, science, English, and language support.',
 'Tuition Centers Near You | My City Info',
 'tuition centre near me, home tutor, maths tutor, science tutor, private tuition, school subject help, after-school tuition',
 'Find tuition centres and private tutors in your city for all school subjects. Extra support for every student on My City Info.'),

(@education_training_id,
 'Online Courses', 'online-courses', '💻', 50,
 'Discover local institutes and training centres offering online and blended courses in tech, business, design, and more.',
 'Online Courses Near You | My City Info',
 'online courses near me, e-learning, online training, digital skills, professional courses, certification courses, blended learning',
 'Find online and blended learning courses in your city. Upskill at your own pace with local institutes on My City Info.'),

(@education_training_id,
 'Skill Development', 'skill-development', '🛠️', 60,
 'Government and private skill development centres offering vocational training, trades, and job-ready certifications.',
 'Skill Development Centres Near You | My City Info',
 'skill development near me, vocational training, job training, trade skills, government skill centre, NSDC, ITI, apprenticeship',
 'Find skill development and vocational training centres in your city. Build job-ready skills with My City Info.'),

(@education_training_id,
 'Language Classes', 'language-classes', '🗣️', 70,
 'Learn English, Hindi, French, German, Spanish, Japanese, and other languages at local language institutes and classes.',
 'Language Classes Near You | My City Info',
 'language classes near me, spoken English, French classes, German language, Spanish classes, IELTS coaching, foreign language',
 'Find language classes in your city for English, French, German, and more. Communicate confidently with My City Info.'),

(@education_training_id,
 'Music Classes', 'music-classes', '🎵', 80,
 'Learn guitar, piano, violin, keyboard, vocals, and tabla at music schools and institutes for all age groups.',
 'Music Classes Near You | My City Info',
 'music classes near me, guitar lessons, piano classes, singing lessons, vocal training, tabla, violin, keyboard classes',
 'Find music classes in your city for all instruments and genres. Learn music from experienced teachers on My City Info.'),

(@education_training_id,
 'Dance Classes', 'dance-classes', '💃', 90,
 'Join dance academies for Bharatanatyam, Bollywood, hip hop, contemporary, salsa, and classical dance forms.',
 'Dance Classes Near You | My City Info',
 'dance classes near me, Bollywood dance, classical dance, Bharatanatyam, hip hop dance, salsa classes, dance academy',
 'Find dance classes in your city for all styles and ages. Learn to dance with top instructors on My City Info.'),

(@education_training_id,
 'Competitive Exam Coaching', 'competitive-exam-coaching', '🏆', 100,
 'Prepare for government and banking exams including SSC, IBPS, RRB, NDA, and state PSC exams with expert coaching.',
 'Competitive Exam Coaching Near You | My City Info',
 'competitive exam coaching near me, SSC coaching, bank exam, IBPS, RRB, NDA coaching, government exam, PSC coaching',
 'Find competitive exam coaching centres in your city for SSC, banking, NDA, and more on My City Info.'),

(@education_training_id,
 'Art Classes', 'art-classes', '🖌️', 110,
 'Explore painting, sketching, pottery, and other visual art forms at local art studios and classes for all age groups.',
 'Art Classes Near You | My City Info',
 'art classes near me, painting classes, sketching, pottery, drawing classes, art studio, visual arts, creative classes',
 'Find art classes in your city for painting, drawing, pottery, and more. Nurture creativity with My City Info.'),

(@education_training_id,
 'Robotics / STEM Classes', 'robotics-stem-classes', '🤖', 120,
 'Enrol kids in robotics, coding, STEM, and science experiment classes to build critical thinking and tech skills early.',
 'Robotics & STEM Classes Near You | My City Info',
 'robotics classes near me, STEM education, coding for kids, science classes, Lego robotics, programming for children, tech education',
 'Find robotics and STEM classes in your city for children. Build future-ready skills in coding and science on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Fitness & Sports
-- ============================================================

SET @fitness_sports_id = (SELECT id FROM mci_categories WHERE slug = 'fitness-sports' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@fitness_sports_id,
 'Gyms', 'gyms', '🏋️', 10,
 'Find well-equipped gyms for weight training, cardio, and full-body fitness. With personal training and group classes available.',
 'Gyms Near You | My City Info',
 'gym near me, fitness centre, weight training, cardio gym, 24 hour gym, personal training, gym membership, workout gym',
 'Find the best gyms in your city for weight training, cardio, and fitness classes. Join a gym near you on My City Info.'),

(@fitness_sports_id,
 'Yoga Classes', 'yoga-classes', '🧘', 20,
 'Practice hatha, power, prenatal, and therapeutic yoga with certified instructors at studios or outdoor sessions.',
 'Yoga Classes Near You | My City Info',
 'yoga classes near me, yoga studio, hatha yoga, power yoga, prenatal yoga, yoga instructor, yoga centre, meditation yoga',
 'Find yoga classes and studios in your city for all levels. Connect mind, body, and spirit with My City Info.'),

(@fitness_sports_id,
 'Personal Trainers', 'personal-trainers', '💪', 30,
 'Hire certified personal trainers for customised workout plans, weight loss, muscle building, and home fitness sessions.',
 'Personal Trainers Near You | My City Info',
 'personal trainer near me, fitness trainer, home workout, weight loss trainer, muscle building, PT sessions, certified trainer',
 'Find certified personal trainers in your city for customised fitness plans. Reach your health goals with My City Info.'),

(@fitness_sports_id,
 'Sports Clubs', 'sports-clubs', '⚽', 40,
 'Join local sports clubs for football, cricket, badminton, tennis, and other sports for leisure, training, and tournaments.',
 'Sports Clubs Near You | My City Info',
 'sports club near me, football club, cricket club, badminton club, tennis club, sports association, local sports',
 'Find sports clubs in your city for football, cricket, badminton, and more. Join a team and play on My City Info.'),

(@fitness_sports_id,
 'Swimming Pools', 'swimming-pools', '🏊', 50,
 'Find public and private swimming pools offering open swim sessions, classes, and competitive training.',
 'Swimming Pools Near You | My City Info',
 'swimming pool near me, swim classes, swimming lessons, Olympic pool, public pool, kids swimming, aquatics centre',
 'Find swimming pools in your city for lessons, lap swimming, and competitive training on My City Info.'),

(@fitness_sports_id,
 'Martial Arts Training', 'martial-arts-training', '🥋', 60,
 'Learn karate, taekwondo, judo, boxing, MMA, and self-defence from certified martial arts instructors near you.',
 'Martial Arts Training Near You | My City Info',
 'martial arts near me, karate classes, taekwondo, judo, MMA training, boxing classes, self-defence, martial arts academy',
 'Find martial arts training centres in your city for karate, MMA, and self-defence. Enrol now on My City Info.'),

(@fitness_sports_id,
 'Zumba / Aerobics', 'zumba-aerobics', '🎶', 70,
 'Join energetic Zumba and aerobics classes for a fun, dance-based workout that burns calories and boosts mood.',
 'Zumba & Aerobics Classes Near You | My City Info',
 'Zumba classes near me, aerobics class, dance fitness, group workout, fitness dance, Zumba instructor, aerobics instructor',
 'Find Zumba and aerobics classes in your city for fun, dance-based fitness. Get moving with My City Info.'),

(@fitness_sports_id,
 'Cricket Academies', 'cricket-academies', '🏏', 80,
 'Train with experienced cricket coaches at local academies offering batting, bowling, and fielding coaching for all age groups.',
 'Cricket Academies Near You | My City Info',
 'cricket academy near me, cricket coaching, batting coaching, bowling coaching, cricket training, junior cricket, cricket camp',
 'Find cricket academies in your city for professional coaching and development. Nurture your cricketing talent on My City Info.'),

(@fitness_sports_id,
 'Cycling Groups', 'cycling-groups', '🚴', 90,
 'Join local cycling clubs and groups for morning rides, long-distance cycling events, and cycling fitness programmes.',
 'Cycling Groups Near You | My City Info',
 'cycling group near me, cycling club, morning cycle ride, bicycle group, cycling event, cycle fitness, city cycling',
 'Find cycling groups and clubs in your city for recreational and competitive riding. Join a ride on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Travel & Hospitality
-- ============================================================

SET @travel_hospitality_id = (SELECT id FROM mci_categories WHERE slug = 'travel-hospitality' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@travel_hospitality_id,
 'Hotels', 'hotels', '🏨', 10,
 'Book comfortable hotels ranging from budget stays to luxury properties for business and leisure travel.',
 'Hotels Near You | My City Info',
 'hotels near me, budget hotel, luxury hotel, business hotel, hotel booking, 3 star hotel, 5 star hotel, hotel stay',
 'Find hotels in your city from budget to luxury. Book rooms for business and leisure travel on My City Info.'),

(@travel_hospitality_id,
 'Resorts', 'resorts', '🏖️', 20,
 'Escape to resorts for relaxing getaways with pools, spas, nature trails, and world-class amenities.',
 'Resorts Near You | My City Info',
 'resorts near me, luxury resort, weekend getaway, pool resort, eco resort, family resort, holiday resort, hill resort',
 'Find resorts near your city for relaxing weekend getaways and family holidays. Book your escape on My City Info.'),

(@travel_hospitality_id,
 'Homestays', 'homestays', '🏡', 30,
 'Experience local culture and hospitality with unique homestays run by local families across the city and nearby areas.',
 'Homestays Near You | My City Info',
 'homestay near me, home stay, B&B, bed and breakfast, local stay, family homestay, cultural stay, affordable accommodation',
 'Find authentic homestays in and around your city for unique, local experiences. Book a homestay on My City Info.'),

(@travel_hospitality_id,
 'Travel Agencies', 'travel-agencies', '✈️', 40,
 'Plan your holiday with full-service travel agencies offering domestic and international tour packages and flight bookings.',
 'Travel Agencies Near You | My City Info',
 'travel agency near me, tour packages, holiday packages, international travel, domestic travel, flight booking, travel agent',
 'Find travel agencies in your city for holiday packages, flights, and tour planning. Book your dream trip on My City Info.'),

(@travel_hospitality_id,
 'Tour Operators', 'tour-operators', '🗺️', 50,
 'Join guided group tours and customised itineraries with experienced local tour operators for memorable travel experiences.',
 'Tour Operators Near You | My City Info',
 'tour operator near me, guided tour, group tour, customised tour, sightseeing, adventure tour, cultural tour, travel package',
 'Find tour operators in your city for guided and customised tour experiences. Explore with local experts on My City Info.'),

(@travel_hospitality_id,
 'Ticket Booking', 'ticket-booking', '🎫', 60,
 'Book train tickets, bus tickets, flight tickets, and event passes through local ticketing agents and booking centres.',
 'Ticket Booking Near You | My City Info',
 'ticket booking near me, train ticket, bus ticket, flight ticket, event tickets, travel booking agent, IRCTC agent',
 'Find ticket booking agents in your city for train, bus, flight, and event tickets. Book hassle-free on My City Info.'),

(@travel_hospitality_id,
 'Car Rentals (Travel)', 'car-rentals-travel', '🚌', 70,
 'Book cars and coaches for outstation travel, airport transfers, and long-distance road trips with professional drivers.',
 'Car Rentals for Travel Near You | My City Info',
 'car rental for travel, outstation cab, airport transfer, taxi booking, travel car hire, coach hire, driver on hire',
 'Find car and coach rentals in your city for outstation travel and airport transfers on My City Info.'),

(@travel_hospitality_id,
 'Visa Services', 'visa-services', '📋', 80,
 'Get professional assistance with visa applications, documentation, and travel insurance for international trips.',
 'Visa Services Near You | My City Info',
 'visa services near me, visa agent, visa application, tourist visa, work visa, travel documentation, visa consultant',
 'Find visa service agents in your city for hassle-free visa applications and travel documentation on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Real Estate
-- ============================================================

SET @real_estate_id = (SELECT id FROM mci_categories WHERE slug = 'real-estate' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@real_estate_id,
 'Property Dealers', 'property-dealers', '🏠', 10,
 'Connect with trusted property dealers and brokers for buying, selling, and renting residential and commercial properties.',
 'Property Dealers Near You | My City Info',
 'property dealer near me, real estate agent, property broker, buy property, sell property, house for sale, flat for rent',
 'Find property dealers in your city for buying, selling, and renting homes and commercial spaces on My City Info.'),

(@real_estate_id,
 'Builders & Developers', 'builders-developers', '🏗️', 20,
 'Discover reputed builders and real estate developers offering residential apartments, villas, and commercial projects.',
 'Builders & Developers Near You | My City Info',
 'builders near me, real estate developer, apartment builder, residential project, villa project, new construction, property developer',
 'Find top builders and developers in your city for new apartments, villas, and commercial projects on My City Info.'),

(@real_estate_id,
 'Rental Services', 'rental-services', '🔑', 30,
 'Find residential and commercial spaces for rent including flats, houses, shops, and offices at competitive rates.',
 'Rental Services Near You | My City Info',
 'rental services near me, flat for rent, house for rent, office for rent, commercial space rent, shop for rent, property rental',
 'Find rental properties in your city for homes, offices, and commercial spaces. Rent smartly with My City Info.'),

(@real_estate_id,
 'PG / Hostels', 'pg-hostels', '🛏️', 40,
 'Find affordable PG accommodations and hostels for students and working professionals with meals and amenities included.',
 'PG & Hostels Near You | My City Info',
 'PG near me, hostel near me, paying guest, student accommodation, working professional PG, furnished PG, girls PG, boys PG',
 'Find PG accommodations and hostels in your city for students and professionals. Affordable stays on My City Info.'),

(@real_estate_id,
 'Commercial Spaces', 'commercial-spaces', '🏢', 50,
 'Lease or purchase office spaces, retail shops, showrooms, warehouses, and commercial plots for business use.',
 'Commercial Spaces Near You | My City Info',
 'commercial space near me, office space, shop for lease, showroom, warehouse, commercial property, coworking space',
 'Find commercial spaces in your city for offices, shops, and warehouses. Lease or buy commercial property on My City Info.'),

(@real_estate_id,
 'Property Consultants', 'property-consultants', '📊', 60,
 'Get expert real estate advice on investment, valuation, legal due diligence, and portfolio management from property consultants.',
 'Property Consultants Near You | My City Info',
 'property consultant near me, real estate advisor, property investment, property valuation, real estate consultation',
 'Find property consultants in your city for investment advice, valuation, and legal guidance on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Logistics & Delivery
-- ============================================================

SET @logistics_delivery_id = (SELECT id FROM mci_categories WHERE slug = 'logistics-delivery' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@logistics_delivery_id,
 'Courier Services', 'courier-services', '📮', 10,
 'Send parcels, documents, and packages locally and nationally through reliable courier and express delivery services.',
 'Courier Services Near You | My City Info',
 'courier service near me, parcel delivery, express courier, document delivery, national courier, local delivery, courier pickup',
 'Find courier services in your city for fast and reliable parcel and document delivery on My City Info.'),

(@logistics_delivery_id,
 'Packers & Movers', 'packers-movers', '🚚', 20,
 'Hire professional packers and movers for home and office relocation, safe packing, loading, and transportation services.',
 'Packers & Movers Near You | My City Info',
 'packers and movers near me, house shifting, office relocation, moving services, home shifting, relocation company, safe packing',
 'Find trusted packers and movers in your city for home and office relocation. Safe and stress-free moving on My City Info.'),

(@logistics_delivery_id,
 'Transport Services', 'transport-services', '🚛', 30,
 'Hire trucks, tempos, and commercial vehicles for goods transportation within the city and outstation.',
 'Transport Services Near You | My City Info',
 'transport service near me, truck hire, tempo hire, goods transport, freight service, commercial vehicle hire, cargo transport',
 'Find transport and freight services in your city for goods movement and cargo delivery on My City Info.'),

(@logistics_delivery_id,
 'Warehousing', 'warehousing', '🏭', 40,
 'Rent warehouse and storage spaces for goods, inventory, and equipment on short-term and long-term basis.',
 'Warehousing Services Near You | My City Info',
 'warehouse near me, storage space, warehousing services, cold storage, fulfillment centre, godown, inventory storage',
 'Find warehousing and storage solutions in your city for goods and inventory management on My City Info.'),

(@logistics_delivery_id,
 'Last Mile Delivery', 'last-mile-delivery', '🛵', 50,
 'Hyperlocal and last mile delivery services for e-commerce, food, and business deliveries within the city.',
 'Last Mile Delivery Services Near You | My City Info',
 'last mile delivery near me, hyperlocal delivery, same day delivery, local delivery, e-commerce delivery, delivery partner',
 'Find last mile delivery services in your city for fast hyperlocal and e-commerce fulfillment on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Events & Entertainment
-- ============================================================

SET @events_entertainment_id = (SELECT id FROM mci_categories WHERE slug = 'events-entertainment' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@events_entertainment_id,
 'Event Planners', 'event-planners', '📋', 10,
 'Plan corporate events, birthdays, anniversaries, and social gatherings with professional event management companies.',
 'Event Planners Near You | My City Info',
 'event planner near me, event management, corporate events, birthday party planner, social events, event organiser',
 'Find event planners in your city for corporate, social, and personal events. Flawless events with My City Info.'),

(@events_entertainment_id,
 'Wedding Planners', 'wedding-planners', '💒', 20,
 'Make your dream wedding a reality with expert wedding planners managing venues, decor, catering, and every detail.',
 'Wedding Planners Near You | My City Info',
 'wedding planner near me, wedding management, wedding coordinator, destination wedding, wedding decor, shaadi planner',
 'Find wedding planners in your city for a perfectly organised and memorable celebration on My City Info.'),

(@events_entertainment_id,
 'DJs', 'djs', '🎧', 30,
 'Hire professional DJs for weddings, parties, corporate events, and nightclub performances with state-of-the-art sound systems.',
 'DJs Near You | My City Info',
 'DJ near me, wedding DJ, party DJ, event DJ, DJ hire, DJ sound system, DJ services, professional DJ',
 'Find professional DJs in your city for weddings, parties, and events. Great music guaranteed on My City Info.'),

(@events_entertainment_id,
 'Photographers', 'photographers', '📸', 40,
 'Capture life''s best moments with professional photographers for weddings, events, portraits, and commercial shoots.',
 'Photographers Near You | My City Info',
 'photographer near me, wedding photographer, event photography, portrait photographer, commercial photography, photo studio',
 'Find talented photographers in your city for weddings, events, and portraits. Beautiful memories on My City Info.'),

(@events_entertainment_id,
 'Videographers', 'videographers', '🎬', 50,
 'Hire videographers and cinematographers for wedding films, event coverage, corporate videos, and social media reels.',
 'Videographers Near You | My City Info',
 'videographer near me, wedding videography, event video, cinematographer, corporate video, video production, reels creator',
 'Find videographers in your city for wedding films, events, and corporate productions on My City Info.'),

(@events_entertainment_id,
 'Party Halls', 'party-halls', '🎊', 60,
 'Book banquet halls, party venues, and function rooms for birthdays, anniversaries, receptions, and social gatherings.',
 'Party Halls Near You | My City Info',
 'party hall near me, banquet hall, function hall, birthday venue, wedding hall, event venue, party venue booking',
 'Find party halls and banquet venues in your city for all occasions. Book a venue easily with My City Info.'),

(@events_entertainment_id,
 'Decorators', 'decorators', '🎈', 70,
 'Transform any space with creative event decorators offering balloon decor, floral arrangements, stage setup, and theme decor.',
 'Event Decorators Near You | My City Info',
 'event decorator near me, balloon decor, floral decoration, stage setup, theme party decor, wedding decoration, birthday decoration',
 'Find event decorators in your city for birthdays, weddings, and special events. Beautiful setups on My City Info.'),

(@events_entertainment_id,
 'Caterers (Events)', 'caterers-events', '🍽️', 80,
 'Hire event caterers for weddings, corporate lunches, parties, and large gatherings with customised menus.',
 'Event Caterers Near You | My City Info',
 'event caterer near me, wedding catering, party catering, corporate lunch, buffet catering, outdoor catering, food for events',
 'Find event caterers in your city for weddings, parties, and corporate events. Delicious food delivered on My City Info.'),

(@events_entertainment_id,
 'Live Music Venues', 'live-music-venues', '🎸', 90,
 'Discover venues hosting live music performances, open mic nights, concerts, and band gigs for music lovers.',
 'Live Music Venues Near You | My City Info',
 'live music near me, open mic, music venue, concert venue, band performance, live performance, music events',
 'Find live music venues in your city for concerts, open mics, and band performances. Live music awaits on My City Info.'),

(@events_entertainment_id,
 'Comedy Shows', 'comedy-shows', '🎭', 100,
 'Find stand-up comedy shows, open mic events, and comedy nights for a great evening of laughter and entertainment.',
 'Comedy Shows Near You | My City Info',
 'comedy show near me, stand-up comedy, open mic comedy, comedy night, comedy event, comedy club, improv show',
 'Find comedy shows and open mics in your city for a night of laughter and entertainment on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Pets & Animals
-- ============================================================

SET @pets_animals_id = (SELECT id FROM mci_categories WHERE slug = 'pets-animals' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@pets_animals_id,
 'Pet Shops', 'pet-shops', '🐕', 10,
 'Buy pets, pet food, accessories, cages, and toys from well-stocked local pet shops and breeders.',
 'Pet Shops Near You | My City Info',
 'pet shop near me, buy puppy, cat for sale, pet food, pet accessories, aquarium shop, bird shop, pet store',
 'Find pet shops in your city for pets, food, accessories, and supplies. Shop for your pet on My City Info.'),

(@pets_animals_id,
 'Veterinary Clinics', 'veterinary-clinics', '🐾', 20,
 'Take your pets to certified veterinary clinics for health check-ups, vaccinations, surgeries, and emergency care.',
 'Veterinary Clinics Near You | My City Info',
 'vet near me, veterinary clinic, animal hospital, pet doctor, dog vet, cat vet, pet vaccination, animal surgery',
 'Find veterinary clinics in your city for pet health check-ups, vaccinations, and care on My City Info.'),

(@pets_animals_id,
 'Pet Grooming', 'pet-grooming', '✂️', 30,
 'Pamper your pets with professional grooming services including bathing, trimming, nail cutting, and coat care.',
 'Pet Grooming Near You | My City Info',
 'pet grooming near me, dog grooming, cat grooming, pet salon, pet bath, fur trimming, dog washing, grooming service',
 'Find pet grooming services in your city for dogs, cats, and other pets. Pamper your pet on My City Info.'),

(@pets_animals_id,
 'Pet Boarding', 'pet-boarding', '🏠', 40,
 'Leave your pets in safe and caring boarding facilities while you travel. Overnight and long-term stays available.',
 'Pet Boarding Near You | My City Info',
 'pet boarding near me, dog boarding, cat boarding, pet hotel, pet kennel, pet care while travelling, overnight pet care',
 'Find pet boarding facilities in your city for safe and comfortable stays while you travel on My City Info.'),

(@pets_animals_id,
 'Pet Training', 'pet-training', '🎓', 50,
 'Train your dogs and pets with certified animal trainers for obedience, behaviour correction, and agility training.',
 'Pet Training Near You | My City Info',
 'pet training near me, dog trainer, obedience training, puppy training, behaviour correction, agility training, animal trainer',
 'Find pet trainers in your city for obedience, behaviour, and agility training. Raise a well-behaved pet with My City Info.'),

(@pets_animals_id,
 'Aquarium Shops', 'aquarium-shops', '🐠', 60,
 'Set up beautiful aquariums with fish, corals, plants, tanks, and accessories from local aquarium shops.',
 'Aquarium Shops Near You | My City Info',
 'aquarium shop near me, fish shop, tropical fish, aquarium tank, coral fish, fish food, aquarium accessories, freshwater fish',
 'Find aquarium shops in your city for fish, tanks, plants, and accessories. Create your dream aquarium with My City Info.');


-- ============================================================
-- SUBCATEGORIES — Financial Services
-- ============================================================

SET @financial_services_id = (SELECT id FROM mci_categories WHERE slug = 'financial-services' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@financial_services_id,
 'Banks', 'banks', '🏦', 10,
 'Locate branches of nationalised, private, and cooperative banks for savings accounts, loans, fixed deposits, and banking services.',
 'Banks Near You | My City Info',
 'bank near me, bank branch, ATM, savings account, home loan, personal loan, fixed deposit, banking services',
 'Find bank branches in your city for all banking services. Accounts, loans, and deposits — on My City Info.'),

(@financial_services_id,
 'Insurance Agents', 'insurance-agents', '🛡️', 20,
 'Get advice on life, health, vehicle, and property insurance from certified local insurance agents and brokers.',
 'Insurance Agents Near You | My City Info',
 'insurance agent near me, life insurance, health insurance, vehicle insurance, property insurance, term plan, insurance broker',
 'Find insurance agents in your city for life, health, and vehicle insurance. Get covered with My City Info.'),

(@financial_services_id,
 'Loan Providers', 'loan-providers', '💰', 30,
 'Access personal loans, home loans, business loans, and vehicle loans from local NBFCs, banks, and loan DSAs.',
 'Loan Providers Near You | My City Info',
 'loan provider near me, personal loan, home loan, business loan, vehicle loan, NBFC, loan agent, instant loan',
 'Find loan providers in your city for personal, home, and business loans. Quick approvals with My City Info.'),

(@financial_services_id,
 'Investment Advisors', 'investment-advisors', '📈', 40,
 'Grow your wealth with SEBI-registered investment advisors offering mutual funds, stocks, bonds, and portfolio management.',
 'Investment Advisors Near You | My City Info',
 'investment advisor near me, mutual fund agent, SEBI advisor, financial planner, portfolio management, wealth management',
 'Find investment advisors in your city for mutual funds, stocks, and wealth management on My City Info.'),

(@financial_services_id,
 'Stock Brokers', 'stock-brokers', '📉', 50,
 'Open a demat account and trade in stocks, commodities, and derivatives with registered local stockbrokers.',
 'Stock Brokers Near You | My City Info',
 'stock broker near me, demat account, share trading, commodity trading, equity broker, derivatives, stock market',
 'Find stock brokers in your city for demat accounts, equity trading, and investments on My City Info.'),

(@financial_services_id,
 'Microfinance', 'microfinance', '🤝', 60,
 'Access small loans and group lending through microfinance institutions for self-employed individuals and small businesses.',
 'Microfinance Services Near You | My City Info',
 'microfinance near me, small loans, group lending, NBFC microfinance, self help group, small business loan, MFI',
 'Find microfinance institutions in your city for small loans and group lending solutions on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Repair & Maintenance
-- ============================================================

SET @repair_maintenance_id = (SELECT id FROM mci_categories WHERE slug = 'repair-maintenance' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@repair_maintenance_id,
 'Mobile Repair', 'mobile-repair', '📱', 10,
 'Get cracked screens, batteries, charging ports, and software issues on all smartphone brands fixed quickly at local repair shops.',
 'Mobile Repair Near You | My City Info',
 'mobile repair near me, phone repair, screen replacement, battery replacement, iPhone repair, Samsung repair, smartphone fix',
 'Find mobile repair shops in your city for quick screen, battery, and software fixes on My City Info.'),

(@repair_maintenance_id,
 'Laptop Repair', 'laptop-repair', '💻', 20,
 'Get laptops repaired for screen damage, slow performance, virus removal, keyboard faults, and hardware upgrades.',
 'Laptop Repair Near You | My City Info',
 'laptop repair near me, computer repair, screen repair, virus removal, RAM upgrade, laptop service centre, hard disk repair',
 'Find laptop repair shops in your city for all brands and issues. Fast, reliable service on My City Info.'),

(@repair_maintenance_id,
 'Watch Repair', 'watch-repair', '⌚', 30,
 'Get mechanical and quartz watches serviced, battery replaced, straps changed, and movements overhauled at local watch repair shops.',
 'Watch Repair Near You | My City Info',
 'watch repair near me, watch service, battery replacement watch, strap change, mechanical watch repair, quartz watch service',
 'Find watch repair shops in your city for servicing, battery replacement, and repairs on My City Info.'),

(@repair_maintenance_id,
 'Shoe Repair', 'shoe-repair', '👟', 40,
 'Cobbler services for shoe sole repair, heel replacement, stitching, leather conditioning, and shoe polishing.',
 'Shoe Repair Near You | My City Info',
 'shoe repair near me, cobbler, sole repair, heel replacement, shoe stitching, leather shoe repair, boot repair',
 'Find shoe repair cobblers in your city for sole, heel, and stitching repairs. Quality shoe care on My City Info.'),

(@repair_maintenance_id,
 'Electronics Repair', 'electronics-repair', '🔌', 50,
 'Repair televisions, audio equipment, CCTV systems, and other electronic devices at local electronics repair shops.',
 'Electronics Repair Near You | My City Info',
 'electronics repair near me, TV repair, LED repair, audio system repair, CCTV repair, electronic device fix, home appliance repair',
 'Find electronics repair shops in your city for TVs, audio systems, and other devices on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Manufacturing & Industrial
-- ============================================================

SET @manufacturing_industrial_id = (SELECT id FROM mci_categories WHERE slug = 'manufacturing-industrial' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@manufacturing_industrial_id,
 'Small Manufacturers', 'small-manufacturers', '🏭', 10,
 'Find small-scale manufacturers and micro enterprises for custom production of goods, components, and consumer products.',
 'Small Manufacturers Near You | My City Info',
 'small manufacturer near me, local manufacturer, custom production, micro enterprise, MSME, small industry, cottage industry',
 'Find small manufacturers in your city for custom and bulk production of goods on My City Info.'),

(@manufacturing_industrial_id,
 'Industrial Equipment', 'industrial-equipment', '⚙️', 20,
 'Source and service industrial machinery, heavy equipment, tools, and production line components from local suppliers.',
 'Industrial Equipment Near You | My City Info',
 'industrial equipment near me, machinery supplier, heavy equipment, industrial tools, manufacturing equipment, plant machinery',
 'Find industrial equipment suppliers in your city for machinery, tools, and production components on My City Info.'),

(@manufacturing_industrial_id,
 'Fabrication Services', 'fabrication-services', '🔩', 30,
 'Metal fabrication, welding, sheet metal work, and structural steel fabrication services for construction and industrial use.',
 'Fabrication Services Near You | My City Info',
 'fabrication near me, metal fabrication, welding services, sheet metal, structural steel, iron fabrication, custom fabrication',
 'Find fabrication and welding services in your city for metal, steel, and custom structural work on My City Info.'),

(@manufacturing_industrial_id,
 'Printing Services', 'printing-services', '🖨️', 40,
 'Get offset, digital, and large-format printing for brochures, banners, visiting cards, packaging, and marketing materials.',
 'Printing Services Near You | My City Info',
 'printing services near me, offset printing, digital printing, banner printing, visiting cards, brochure printing, flex printing',
 'Find printing services in your city for all your marketing and packaging print needs on My City Info.'),

(@manufacturing_industrial_id,
 'Packaging Services', 'packaging-services', '📦', 50,
 'Source custom packaging solutions for products including boxes, pouches, labels, and eco-friendly packaging options.',
 'Packaging Services Near You | My City Info',
 'packaging services near me, custom packaging, product boxes, pouches, label printing, eco packaging, corrugated boxes',
 'Find packaging solution providers in your city for custom boxes, labels, and eco-friendly options on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Local Services
-- ============================================================

SET @local_services_id = (SELECT id FROM mci_categories WHERE slug = 'local-services' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@local_services_id,
 'Tailors', 'tailors', '🧵', 10,
 'Get custom clothing stitched and altered by skilled local tailors for men, women, and children at affordable prices.',
 'Tailors Near You | My City Info',
 'tailor near me, stitching, alterations, custom clothing, mens tailor, ladies tailor, blouse stitching, dress alteration',
 'Find skilled tailors in your city for custom stitching and clothing alterations on My City Info.'),

(@local_services_id,
 'Laundry Services', 'laundry-services', '👕', 20,
 'Fresh and clean clothes with professional laundry, dry cleaning, ironing, and wash-and-fold services near you.',
 'Laundry Services Near You | My City Info',
 'laundry near me, dry cleaning, clothes ironing, wash and fold, laundry pickup, garment cleaning, laundry service',
 'Find laundry and dry cleaning services in your city for fresh, clean clothes delivered to your door on My City Info.'),

(@local_services_id,
 'Photocopy / Printing Shops', 'photocopy-printing-shops', '🖨️', 30,
 'Get photocopies, document printing, lamination, and binding services at local print shops and stationery stores.',
 'Photocopy & Printing Shops Near You | My City Info',
 'photocopy near me, xerox shop, document printing, lamination, binding, ID card printing, printing shop',
 'Find photocopy and printing shops in your city for documents, lamination, and binding on My City Info.'),

(@local_services_id,
 'Internet Cafes', 'internet-cafes', '🖥️', 40,
 'Access the internet, print documents, and use computer services at local internet cafes and browsing centres.',
 'Internet Cafes Near You | My City Info',
 'internet cafe near me, browsing centre, cyber cafe, computer for rent, gaming cafe, internet access, net cafe',
 'Find internet cafes and browsing centres in your city for internet access, printing, and computer services on My City Info.'),

(@local_services_id,
 'Recharge Shops', 'recharge-shops', '📲', 50,
 'Recharge mobile phones, DTH, and data cards, and pay utility bills at local mobile recharge shops.',
 'Recharge Shops Near You | My City Info',
 'recharge shop near me, mobile recharge, DTH recharge, data card, bill payment, prepaid recharge, utility bill payment',
 'Find recharge shops in your city for mobile, DTH, and utility bill payments on My City Info.'),

(@local_services_id,
 'Key Makers / Locksmiths', 'key-makers-locksmiths', '🔑', 60,
 'Get duplicate keys made and get locked out of homes or vehicles sorted by local locksmiths and key makers.',
 'Locksmiths Near You | My City Info',
 'locksmith near me, key maker, duplicate key, lock repair, emergency locksmith, car key replacement, house lockout',
 'Find locksmiths and key makers in your city for duplicate keys, lock repairs, and emergency lockouts on My City Info.'),

(@local_services_id,
 'Security Guard Services', 'security-guard-services', '💂', 70,
 'Hire trained security guards and security agencies for residential societies, offices, events, and commercial establishments.',
 'Security Guard Services Near You | My City Info',
 'security guard near me, security agency, trained guards, residential security, corporate security, event security, CCTV guard',
 'Find security guard agencies in your city for homes, offices, and events on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Religious & Community
-- ============================================================

SET @religious_community_id = (SELECT id FROM mci_categories WHERE slug = 'religious-community' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@religious_community_id,
 'Temples', 'temples', '🛕', 10,
 'Find Hindu temples, mandirs, and religious shrines for worship, festivals, and spiritual ceremonies in your city.',
 'Temples Near You | My City Info',
 'temple near me, mandir, Hindu temple, Shiva temple, Ganesh temple, religious places, worship, festival venue',
 'Find temples and Hindu religious sites in your city for worship and festivals on My City Info.'),

(@religious_community_id,
 'Mosques', 'mosques', '🕌', 20,
 'Locate mosques and Islamic centres for daily prayers, Jumu''ah services, and Islamic education near you.',
 'Mosques Near You | My City Info',
 'mosque near me, masjid, Islamic centre, Friday prayer, Muslim prayer, Islamic religious place, namaz',
 'Find mosques and Islamic centres in your city for prayers and religious services on My City Info.'),

(@religious_community_id,
 'Churches', 'churches', '⛪', 30,
 'Discover Christian churches and cathedrals for Sunday services, baptisms, weddings, and community gatherings.',
 'Churches Near You | My City Info',
 'church near me, Christian church, Catholic church, Protestant church, Sunday service, chapel, cathedral',
 'Find churches and Christian places of worship in your city for services and ceremonies on My City Info.'),

(@religious_community_id,
 'Community Centers', 'community-centers', '🏛️', 40,
 'Access community halls and centres for cultural programmes, social gatherings, public events, and civic meetings.',
 'Community Centers Near You | My City Info',
 'community centre near me, community hall, social gathering, cultural events, public meeting, civic centre, neighbourhood hall',
 'Find community centres in your city for cultural events, social gatherings, and civic programmes on My City Info.'),

(@religious_community_id,
 'NGOs', 'ngos', '🤝', 50,
 'Connect with NGOs, charitable trusts, and non-profits working in education, health, environment, and social welfare.',
 'NGOs Near You | My City Info',
 'NGO near me, non-profit, charitable trust, social welfare, community service, volunteer, donation, social organisation',
 'Find NGOs and charitable organisations in your city for social welfare and community service on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Government & Public Services
-- ============================================================

SET @government_public_services_id = (SELECT id FROM mci_categories WHERE slug = 'government-public-services' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@government_public_services_id,
 'Government Offices', 'government-offices', '🏛️', 10,
 'Locate municipal corporations, tehsil offices, district offices, and other government institutions for civic services.',
 'Government Offices Near You | My City Info',
 'government office near me, municipal corporation, tehsil, collectorate, district office, civic services, government department',
 'Find government offices in your city for civic services, certificates, and administrative needs on My City Info.'),

(@government_public_services_id,
 'Police Stations', 'police-stations', '🚔', 20,
 'Find the nearest police station for filing FIRs, reporting crimes, passport verification, and police assistance.',
 'Police Stations Near You | My City Info',
 'police station near me, FIR, crime reporting, passport verification, police help, law enforcement, emergency police',
 'Find police stations in your city for FIRs, reporting, and police assistance on My City Info.'),

(@government_public_services_id,
 'Post Offices', 'post-offices', '📮', 30,
 'Visit India Post offices for speed post, registered mail, money orders, savings accounts, and postal services.',
 'Post Offices Near You | My City Info',
 'post office near me, India Post, speed post, registered mail, money order, postal savings, Aadhaar at post office',
 'Find post offices in your city for postal, financial, and government services on My City Info.'),

(@government_public_services_id,
 'Public Utilities', 'public-utilities', '💡', 40,
 'Locate electricity boards, water supply offices, gas agencies, and public utility service centres in your city.',
 'Public Utilities Near You | My City Info',
 'electricity office near me, water supply, gas agency, public utility, MSEB, BESCOM, municipal water, utility services',
 'Find public utility offices in your city for electricity, water, gas, and civic infrastructure services on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Agriculture & Farming
-- ============================================================

SET @agriculture_farming_id = (SELECT id FROM mci_categories WHERE slug = 'agriculture-farming' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@agriculture_farming_id,
 'Fertilizer Shops', 'fertilizer-shops', '🌱', 10,
 'Buy chemical and organic fertilizers, soil conditioners, and plant nutrients for crops and gardens from local agri-input dealers.',
 'Fertilizer Shops Near You | My City Info',
 'fertilizer shop near me, chemical fertilizer, organic fertilizer, soil conditioner, plant nutrients, agri inputs, crop nutrition',
 'Find fertilizer shops in your city for crop nutrition and soil health products on My City Info.'),

(@agriculture_farming_id,
 'Seeds Suppliers', 'seeds-suppliers', '🌾', 20,
 'Source certified hybrid, organic, and indigenous seeds for vegetables, fruits, flowers, and field crops from reputed suppliers.',
 'Seeds Suppliers Near You | My City Info',
 'seeds supplier near me, hybrid seeds, organic seeds, vegetable seeds, crop seeds, seed dealer, certified seeds',
 'Find seed suppliers in your city for certified hybrid and organic seeds for all crops on My City Info.'),

(@agriculture_farming_id,
 'Farm Equipment', 'farm-equipment', '🚜', 30,
 'Rent or buy tractors, tillers, harvesting machines, irrigation systems, and other farm equipment from local dealers.',
 'Farm Equipment Near You | My City Info',
 'farm equipment near me, tractor dealer, tiller, harvesting machine, irrigation equipment, agricultural machinery, farm tools',
 'Find farm equipment dealers in your city for tractors, tillers, and irrigation systems on My City Info.'),

(@agriculture_farming_id,
 'Dairy Farms', 'dairy-farms', '🐄', 40,
 'Source fresh milk, paneer, ghee, curd, and dairy products directly from local dairy farms and cooperatives.',
 'Dairy Farms Near You | My City Info',
 'dairy farm near me, fresh milk, paneer, ghee, curd, dairy products, milk supplier, cow farm, buffalo farm',
 'Find dairy farms and cooperatives in your city for fresh milk and dairy products on My City Info.');


-- ============================================================
-- SUBCATEGORIES — Miscellaneous
-- ============================================================

SET @miscellaneous_id = (SELECT id FROM mci_categories WHERE slug = 'miscellaneous' LIMIT 1);
INSERT INTO mci_categories (parent_id, name, slug, icon, sort_order, description, page_title, meta_keywords, meta_description) VALUES

(@miscellaneous_id,
 'Freelancers', 'freelancers', '💻', 10,
 'Connect with skilled freelancers for writing, design, development, photography, and other professional services.',
 'Freelancers Near You | My City Info',
 'freelancers near me, freelance services, freelance writer, freelance designer, freelance developer, gig work, independent professional',
 'Find freelancers in your city for writing, design, development, and more. Hire local talent on My City Info.'),

(@miscellaneous_id,
 'Home-based Businesses', 'home-based-businesses', '🏠', 20,
 'Discover home-based businesses offering food, crafts, tutoring, tailoring, and other services run from residences.',
 'Home-based Businesses Near You | My City Info',
 'home based business near me, home business, home bakery, home tutor, cottage industry, home services business',
 'Find home-based businesses in your city for food, crafts, and personal services on My City Info.'),

(@miscellaneous_id,
 'Startups', 'startups', '🚀', 30,
 'Discover innovative local startups across technology, food, health, education, and social impact sectors.',
 'Startups Near You | My City Info',
 'startups near me, local startups, innovative businesses, tech startup, food startup, edtech, healthtech, social startup',
 'Discover innovative startups in your city across technology, food, and social impact sectors on My City Info.'),

(@miscellaneous_id,
 'Others', 'others', '🔖', 40,
 'Businesses and services that do not fit any other category. If your business is unique, list it here and let customers find you.',
 'Other Local Businesses | My City Info',
 'other businesses near me, unique services, local business, uncategorised business, miscellaneous services',
 'Find unique and uncategorised local businesses in your city. Every business has a home on My City Info.');


COMMIT;
SET foreign_key_checks=1;

-- ============================================================
-- Summary
-- 22 parent categories, 163 subcategories = 185 rows total
-- All fields: name, slug, icon, sort_order, description,
--             page_title, meta_keywords, meta_description
-- parent_id uses SET variable — safe for MySQL/MariaDB
-- Encoding: utf8mb4 (emoji support required)
-- ============================================================
