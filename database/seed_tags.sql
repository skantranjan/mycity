-- MyCityInfo Tags — ready to insert
-- Generated: 2026-03-21
-- Tags are cross-category descriptive attributes applied to individual business listings.
-- They help users filter and discover businesses by attributes, features, and characteristics.
-- Run against your MySQL/MariaDB database on an empty mci_tags table.
-- To re-run: SET foreign_key_checks=0; TRUNCATE TABLE mci_tags; SET foreign_key_checks=1;

SET NAMES utf8mb4;
START TRANSACTION;

-- ============================================================
-- GROUP 1 — Availability & Hours
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Open on Sundays', 'open-on-sundays',
 'Businesses that remain open on Sundays, ideal for customers who can only visit on weekends.',
 'Open on Sundays Near You | My City Info',
 'open Sunday, Sunday business, weekend open, shops open Sunday, services Sunday',
 'Find businesses open on Sundays in your city. Shops, services, and eateries available on weekends on My City Info.'),

('Open 24 Hours', 'open-24-hours',
 'Businesses and services that operate round the clock, 24 hours a day, 7 days a week.',
 'Open 24 Hours Near You | My City Info',
 '24 hour business, open all night, 24/7 service, round the clock, always open, night service',
 'Find businesses open 24 hours in your city. Round-the-clock services and shops listed on My City Info.'),

('Open on Public Holidays', 'open-on-public-holidays',
 'Businesses that stay open during national and state public holidays.',
 'Open on Public Holidays | My City Info',
 'open holiday, business open holiday, public holiday service, open Diwali, open Holi',
 'Find businesses open on public holidays in your city. Never be stuck without a service on My City Info.'),

('Early Morning Hours', 'early-morning-hours',
 'Businesses that open early in the morning, typically before 8 AM — gyms, milk vendors, bakeries, etc.',
 'Early Morning Businesses Near You | My City Info',
 'early morning open, opens before 8am, morning service, early gym, early breakfast',
 'Find early-opening businesses in your city. Morning gyms, bakeries, and services on My City Info.'),

('Late Night Hours', 'late-night-hours',
 'Businesses that operate late into the night, past 10 PM — restaurants, pharmacies, convenience stores, etc.',
 'Late Night Businesses Near You | My City Info',
 'late night open, open after 10pm, night restaurant, late night pharmacy, night service, 24 hour store',
 'Find late-night businesses in your city. Restaurants, pharmacies, and shops open past 10 PM on My City Info.'),

('Appointment Only', 'appointment-only',
 'Businesses that operate strictly by appointment — doctors, consultants, salons, etc.',
 'Appointment Only Services Near You | My City Info',
 'appointment only, book appointment, by appointment, scheduled visit, prior appointment required',
 'Find appointment-only services in your city. Book consultations and visits in advance on My City Info.'),

('Walk-ins Welcome', 'walk-ins-welcome',
 'Businesses that accept customers without prior appointments — drop-in friendly.',
 'Walk-in Services Near You | My City Info',
 'walk-in welcome, no appointment needed, drop-in, same day service, instant service',
 'Find businesses that welcome walk-ins in your city. No appointment needed — just drop in on My City Info.');


-- ============================================================
-- GROUP 2 — Delivery & Service Mode
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Home Delivery', 'home-delivery',
 'Businesses that deliver their products or services directly to your home or doorstep.',
 'Home Delivery Services Near You | My City Info',
 'home delivery, doorstep delivery, delivery available, delivers to home, free delivery, same day delivery',
 'Find businesses offering home delivery in your city. Food, groceries, medicines, and more delivered to you on My City Info.'),

('Home Visits', 'home-visits',
 'Service professionals who visit customers at home — doctors, nurses, physiotherapists, beauty services, etc.',
 'Home Visit Services Near You | My City Info',
 'home visit, doctor home visit, nurse at home, home service, doorstep service, at home professional',
 'Find professionals who offer home visits in your city. Doctors, nurses, beauty services, and more on My City Info.'),

('Online Services', 'online-services',
 'Businesses that offer their services remotely via video call, phone, or internet — consultants, tutors, doctors, etc.',
 'Online Services Near You | My City Info',
 'online service, remote service, virtual consultation, online doctor, online tutor, digital service, teleconsult',
 'Find businesses offering online and remote services in your city. Consult from home on My City Info.'),

('Free Delivery', 'free-delivery',
 'Businesses that offer free delivery above a certain order value or unconditionally.',
 'Free Delivery Near You | My City Info',
 'free delivery, no delivery charge, free shipping, zero delivery fee, free home delivery',
 'Find businesses offering free delivery in your city. No extra delivery charges on My City Info.'),

('Same Day Delivery', 'same-day-delivery',
 'Businesses that deliver orders on the same day they are placed.',
 'Same Day Delivery Near You | My City Info',
 'same day delivery, instant delivery, express delivery, today delivery, quick delivery, fast shipping',
 'Find same-day delivery services in your city. Get your order delivered today on My City Info.'),

('Pickup Available', 'pickup-available',
 'Businesses that allow customers to order online or by phone and pick up in-store at their convenience.',
 'Pickup Available Near You | My City Info',
 'store pickup, click and collect, pickup available, self pickup, order and collect, in-store pickup',
 'Find businesses offering in-store pickup in your city. Order ahead and collect at your convenience on My City Info.'),

('Doorstep Service', 'doorstep-service',
 'Service providers who come to your location — cleaning, repair, beauty, installation, and maintenance services.',
 'Doorstep Services Near You | My City Info',
 'doorstep service, at home service, on-site service, come to your location, mobile service, doorstep repair',
 'Find doorstep service providers in your city. Repairs, cleaning, beauty, and more at your location on My City Info.');


-- ============================================================
-- GROUP 3 — Pricing & Value
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Budget-Friendly', 'budget-friendly',
 'Affordable businesses offering good value for money without compromising on quality.',
 'Budget-Friendly Businesses Near You | My City Info',
 'budget friendly, affordable, cheap, low cost, value for money, economical, inexpensive',
 'Find budget-friendly businesses in your city. Quality services at affordable prices on My City Info.'),

('Premium / Luxury', 'premium-luxury',
 'High-end businesses offering premium products, services, and experiences for discerning customers.',
 'Premium & Luxury Businesses Near You | My City Info',
 'premium, luxury, high end, exclusive, upscale, fine, top quality, 5 star, deluxe',
 'Discover premium and luxury businesses in your city for the finest products and experiences on My City Info.'),

('Free Consultation', 'free-consultation',
 'Businesses that offer a free first consultation — lawyers, doctors, consultants, financial advisors, etc.',
 'Free Consultation Near You | My City Info',
 'free consultation, no charge consultation, free first meeting, free advice, complimentary consultation',
 'Find businesses offering free consultations in your city. Get expert advice at no cost on My City Info.'),

('EMI Available', 'emi-available',
 'Businesses that offer EMI or instalment payment options for products and services.',
 'EMI Available Near You | My City Info',
 'EMI available, no cost EMI, instalment payment, pay in parts, monthly payment, zero interest EMI',
 'Find businesses offering EMI and instalment payment options in your city on My City Info.'),

('Discounts & Offers', 'discounts-offers',
 'Businesses currently running special discounts, seasonal offers, or promotional deals.',
 'Discounts & Offers Near You | My City Info',
 'discounts, offers, deals, sale, special offer, promotional price, coupon, seasonal discount',
 'Find businesses with active discounts and special offers in your city on My City Info.'),

('No Hidden Charges', 'no-hidden-charges',
 'Transparent businesses that clearly state their pricing with no surprise fees or hidden charges.',
 'No Hidden Charges | My City Info',
 'no hidden charges, transparent pricing, clear pricing, no extra fees, honest pricing, fixed price',
 'Find businesses with transparent pricing in your city. No hidden fees or surprises on My City Info.');


-- ============================================================
-- GROUP 4 — Food & Dietary Preferences
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Pure Veg', 'pure-veg',
 'Restaurants and food businesses that serve only pure vegetarian food — no meat, fish, or eggs.',
 'Pure Veg Restaurants Near You | My City Info',
 'pure veg, vegetarian restaurant, veg only, sattvic food, no meat, veg food, vegetarian food',
 'Find pure vegetarian restaurants and food businesses in your city. 100% veg food on My City Info.'),

('Non-Veg Available', 'non-veg-available',
 'Restaurants and food outlets that serve non-vegetarian food including chicken, mutton, fish, and eggs.',
 'Non-Veg Restaurants Near You | My City Info',
 'non veg, chicken, mutton, fish, egg, non vegetarian restaurant, meat dishes, seafood',
 'Find non-veg restaurants and food outlets in your city for chicken, mutton, fish, and more on My City Info.'),

('Vegan Options', 'vegan-options',
 'Food businesses that offer vegan menu options — no animal products including dairy and eggs.',
 'Vegan Food Near You | My City Info',
 'vegan food, vegan options, plant-based, dairy free, egg free, vegan restaurant, vegan menu',
 'Find businesses with vegan food options in your city. Plant-based and cruelty-free choices on My City Info.'),

('Jain Food', 'jain-food',
 'Restaurants and caterers offering Jain food — strictly vegetarian, no root vegetables (onion, garlic, potato, etc.).',
 'Jain Food Near You | My City Info',
 'Jain food, Jain menu, Jain restaurant, no onion no garlic, Jain catering, Jain thali, sattvic',
 'Find restaurants and caterers offering Jain food in your city. No onion, no garlic menus on My City Info.'),

('Halal', 'halal',
 'Food businesses serving halal-certified meat and food products prepared according to Islamic dietary laws.',
 'Halal Food Near You | My City Info',
 'halal food, halal meat, halal restaurant, halal certified, Muslim food, halal chicken, halal mutton',
 'Find halal food restaurants and meat shops in your city. Certified halal food on My City Info.'),

('Gluten-Free Options', 'gluten-free-options',
 'Restaurants and food businesses that offer gluten-free menu items for customers with dietary restrictions.',
 'Gluten-Free Food Near You | My City Info',
 'gluten free food, gluten free menu, celiac friendly, no gluten, wheat free, gluten free restaurant',
 'Find gluten-free food options in your city. Restaurants and bakeries with gluten-free menus on My City Info.'),

('Organic Food', 'organic-food',
 'Businesses that sell or serve certified organic food, produce, or ingredients.',
 'Organic Food Near You | My City Info',
 'organic food, organic produce, chemical free, natural food, organic vegetables, organic restaurant, farm to table',
 'Find organic food businesses in your city for certified natural produce and meals on My City Info.'),

('Outdoor Seating', 'outdoor-seating',
 'Restaurants, cafes, and venues with outdoor or alfresco seating options.',
 'Outdoor Seating Near You | My City Info',
 'outdoor seating, alfresco, open air dining, terrace restaurant, garden seating, rooftop seating',
 'Find restaurants and cafes with outdoor seating in your city for open-air dining on My City Info.'),

('Home-Style Food', 'home-style-food',
 'Tiffin services, cloud kitchens, and restaurants serving home-cooked, comfort food meals.',
 'Home Style Food Near You | My City Info',
 'home style food, home cooked, ghar ka khana, comfort food, homemade food, tiffin, dabba food',
 'Find home-style cooked food services in your city for nutritious, comforting meals on My City Info.'),

('Takeaway / Parcel', 'takeaway-parcel',
 'Restaurants and food outlets that offer takeaway and parcel packing for eating on the go.',
 'Takeaway Food Near You | My City Info',
 'takeaway, parcel, take away, food to go, packing, carry out, quick food, grab and go',
 'Find restaurants offering takeaway and parcel food in your city. Order to go on My City Info.'),

('Buffet', 'buffet',
 'Restaurants and caterers offering unlimited buffet dining options for lunch, dinner, or events.',
 'Buffet Restaurants Near You | My City Info',
 'buffet near me, unlimited food, lunch buffet, dinner buffet, buffet restaurant, all you can eat, thali',
 'Find buffet restaurants in your city for unlimited dining. Lunch and dinner buffets on My City Info.'),

('BYOB', 'byob',
 'Restaurants that allow customers to bring their own wine or alcohol — Bring Your Own Bottle policy.',
 'BYOB Restaurants Near You | My City Info',
 'BYOB, bring your own bottle, BYOB restaurant, bring own alcohol, no corkage fee',
 'Find BYOB restaurants in your city where you can bring your own alcohol on My City Info.');


-- ============================================================
-- GROUP 5 — Facilities & Amenities
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Parking Available', 'parking-available',
 'Businesses with dedicated or nearby parking facilities for cars and two-wheelers.',
 'Parking Available Near You | My City Info',
 'parking available, free parking, car parking, two-wheeler parking, ample parking, parking space',
 'Find businesses with parking facilities in your city. Convenient parking near shops and services on My City Info.'),

('AC / Air-Conditioned', 'ac-air-conditioned',
 'Businesses with air-conditioned premises for a comfortable indoor experience.',
 'Air-Conditioned Businesses Near You | My City Info',
 'air conditioned, AC restaurant, AC salon, AC gym, AC hall, cool environment, air conditioning',
 'Find air-conditioned businesses in your city for a cool and comfortable experience on My City Info.'),

('WiFi Available', 'wifi-available',
 'Cafes, restaurants, co-working spaces, and other businesses offering free or paid WiFi.',
 'WiFi Available Near You | My City Info',
 'wifi available, free wifi, internet access, cafe with wifi, co-working wifi, high speed internet',
 'Find businesses with WiFi in your city. Work, study, or browse in cafes and co-working spaces on My City Info.'),

('Wheelchair Accessible', 'wheelchair-accessible',
 'Businesses with ramps, lifts, and facilities to accommodate customers in wheelchairs or with mobility challenges.',
 'Wheelchair Accessible Businesses Near You | My City Info',
 'wheelchair accessible, disabled access, ramp access, accessible facility, handicap friendly, mobility friendly',
 'Find wheelchair-accessible businesses in your city. Inclusive and accessible services on My City Info.'),

('Child-Friendly', 'child-friendly',
 'Businesses with facilities or services suitable for children — play areas, kids menus, baby changing rooms, etc.',
 'Child-Friendly Businesses Near You | My City Info',
 'child friendly, kids welcome, family friendly, play area, kids menu, baby friendly, family restaurant',
 'Find child-friendly businesses in your city for families with kids. Welcoming spaces for all ages on My City Info.'),

('Pet-Friendly', 'pet-friendly',
 'Restaurants, hotels, parks, and businesses that welcome pets on their premises.',
 'Pet-Friendly Businesses Near You | My City Info',
 'pet friendly, dogs allowed, pets welcome, dog friendly cafe, pet friendly hotel, bring your pet',
 'Find pet-friendly businesses in your city for dining, stays, and activities with your pets on My City Info.'),

('Private Rooms / Cabins', 'private-rooms-cabins',
 'Businesses offering private rooms or enclosed cabins for privacy — salons, spas, consulting rooms, etc.',
 'Private Rooms Available Near You | My City Info',
 'private room, private cabin, private consultation, enclosed space, privacy, private salon room, private spa',
 'Find businesses with private rooms and cabins in your city for a more personal experience on My City Info.'),

('CCTV Surveillance', 'cctv-surveillance',
 'Businesses with CCTV camera surveillance systems for enhanced security of customers and premises.',
 'CCTV Secured Businesses Near You | My City Info',
 'CCTV, surveillance, security cameras, monitored premises, safe business, secure environment',
 'Find CCTV-secured businesses in your city for a safe and monitored environment on My City Info.'),

('Generator / Power Backup', 'generator-power-backup',
 'Businesses with generators or UPS power backup ensuring uninterrupted operations during power cuts.',
 'Power Backup Available Near You | My City Info',
 'power backup, generator, UPS, no power cut, uninterrupted power, 24 hour power, inverter backup',
 'Find businesses with power backup in your city. Uninterrupted services even during power cuts on My City Info.'),

('Waiting Lounge', 'waiting-lounge',
 'Businesses with dedicated waiting areas, lounges, or seating for customers.',
 'Waiting Lounge Available | My City Info',
 'waiting lounge, waiting area, comfortable wait, seating available, lobby, reception area',
 'Find businesses with comfortable waiting areas in your city. Relaxed waiting on My City Info.'),

('Restrooms Available', 'restrooms-available',
 'Businesses with clean, accessible restrooms and washroom facilities for customers.',
 'Restrooms Available | My City Info',
 'restrooms, washroom available, clean toilet, customer restroom, bathroom facility, hygienic washroom',
 'Find businesses with clean restroom facilities in your city. Hygiene and comfort on My City Info.');


-- ============================================================
-- GROUP 6 — Payment & Transaction
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('UPI Accepted', 'upi-accepted',
 'Businesses that accept UPI payments — Google Pay, PhonePe, Paytm, BHIM, and other UPI apps.',
 'UPI Accepted Near You | My City Info',
 'UPI payment, Google Pay, PhonePe, Paytm, BHIM, digital payment, UPI accepted',
 'Find businesses accepting UPI payments in your city. Pay easily with Google Pay, PhonePe, and more on My City Info.'),

('Credit / Debit Cards', 'credit-debit-cards',
 'Businesses with card payment machines accepting Visa, Mastercard, RuPay, and American Express cards.',
 'Card Payments Accepted | My City Info',
 'credit card, debit card, card payment, POS machine, Visa, Mastercard, RuPay, card accepted',
 'Find businesses accepting credit and debit card payments in your city. Convenient cashless payments on My City Info.'),

('Cash Only', 'cash-only',
 'Businesses that accept cash payments only and do not have card or digital payment facilities.',
 'Cash Only Businesses | My City Info',
 'cash only, no card, cash payment, cash accepted, no UPI, only cash',
 'Find cash-only businesses in your city. Carry cash for these shops and services on My City Info.'),

('Accepts Cheques', 'accepts-cheques',
 'Businesses that accept payment by cheque — typically for large transactions, B2B, or corporate clients.',
 'Cheque Accepted | My City Info',
 'cheque accepted, payment by cheque, account payee cheque, business cheque, corporate payment',
 'Find businesses accepting cheque payments in your city. Suitable for B2B and large transactions on My City Info.'),

('Net Banking', 'net-banking',
 'Businesses that accept payments via internet banking or NEFT/RTGS transfers.',
 'Net Banking Accepted | My City Info',
 'net banking, NEFT, RTGS, internet banking, bank transfer, online payment, wire transfer',
 'Find businesses accepting net banking and bank transfers in your city on My City Info.'),

('Insurance Accepted', 'insurance-accepted',
 'Healthcare and other businesses that accept health insurance or cashless insurance claims.',
 'Insurance Accepted | My City Info',
 'insurance accepted, cashless insurance, health insurance, TPA, mediclaim, insurance claim, cashless hospital',
 'Find businesses accepting insurance and cashless mediclaim in your city. Hassle-free claims on My City Info.');


-- ============================================================
-- GROUP 7 — Customer & Service Quality
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Highly Rated', 'highly-rated',
 'Businesses with consistently high ratings and positive customer reviews.',
 'Highly Rated Businesses Near You | My City Info',
 'highly rated, top rated, 5 star, excellent reviews, best rated, top reviews, customer favourite',
 'Find highly rated businesses in your city. Top-reviewed shops and services on My City Info.'),

('Experienced Professionals', 'experienced-professionals',
 'Businesses staffed by professionals with significant experience and expertise in their field.',
 'Experienced Professionals Near You | My City Info',
 'experienced, seasoned professional, expert, veteran, years of experience, skilled professional',
 'Find businesses with experienced professionals in your city. Skill and expertise you can trust on My City Info.'),

('Certified / Licensed', 'certified-licensed',
 'Businesses with official certifications, licences, or accreditations from relevant authorities.',
 'Certified & Licensed Businesses Near You | My City Info',
 'certified, licensed, accredited, ISO certified, government approved, registered, authorised',
 'Find certified and licensed businesses in your city. Verified credentials and professional standards on My City Info.'),

('ISO Certified', 'iso-certified',
 'Businesses with ISO quality management certifications ensuring international quality standards.',
 'ISO Certified Businesses Near You | My City Info',
 'ISO certified, ISO 9001, quality management, international standard, ISO accreditation, quality certified',
 'Find ISO-certified businesses in your city. Internationally recognised quality standards on My City Info.'),

('Quick Service', 'quick-service',
 'Businesses known for fast turnaround, minimal waiting time, and prompt service delivery.',
 'Quick Service Businesses Near You | My City Info',
 'quick service, fast service, minimal wait, prompt delivery, no wait, express service, fast turnaround',
 'Find businesses offering quick and prompt service in your city. No long waits on My City Info.'),

('Free Estimates / Quotes', 'free-estimates-quotes',
 'Businesses that provide free estimates or quotations before committing to work — plumbers, contractors, IT services, etc.',
 'Free Estimates Near You | My City Info',
 'free estimate, free quote, no charge estimate, free inspection, free assessment, free evaluation',
 'Find businesses offering free estimates and quotes in your city. Know the cost before you commit on My City Info.'),

('Warranty / Guarantee', 'warranty-guarantee',
 'Businesses that offer a warranty or service guarantee on their products or work.',
 'Warranty & Guarantee | My City Info',
 'warranty, guarantee, service guarantee, work warranty, product warranty, after service support',
 'Find businesses with warranty and service guarantees in your city. Confidence in every purchase on My City Info.'),

('After-Sales Support', 'after-sales-support',
 'Businesses providing dedicated after-sales service, maintenance, and support for products sold.',
 'After-Sales Support Near You | My City Info',
 'after sales service, post purchase support, maintenance support, customer care, follow up service',
 'Find businesses with strong after-sales support in your city. Service that continues after the sale on My City Info.'),

('Female Staff Available', 'female-staff-available',
 'Businesses with female professionals available — important for women-centric services like health, beauty, and therapy.',
 'Female Staff Available | My City Info',
 'female staff, lady doctor, women professional, female therapist, female nurse, female trainer, women only',
 'Find businesses with female staff available in your city. Comfortable and safe services for women on My City Info.'),

('Multilingual Staff', 'multilingual-staff',
 'Businesses with staff who can communicate in multiple languages for diverse customers.',
 'Multilingual Staff | My City Info',
 'multilingual, multiple languages, English speaking, Hindi speaking, regional language, language support',
 'Find businesses with multilingual staff in your city. Communicate comfortably in your language on My City Info.');


-- ============================================================
-- GROUP 8 — Business Type & Setup
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Women-Owned', 'women-owned',
 'Businesses founded and operated by women entrepreneurs. Support local women-led businesses.',
 'Women-Owned Businesses Near You | My City Info',
 'women owned business, female entrepreneur, women led, woman-run business, women enterprise',
 'Find women-owned businesses in your city. Support female entrepreneurs in your community on My City Info.'),

('Family Business', 'family-business',
 'Long-established family-run businesses with generational expertise and trusted local presence.',
 'Family Businesses Near You | My City Info',
 'family business, family run, generational business, traditional business, family owned, heritage business',
 'Find trusted family-run businesses in your city. Generations of expertise and community trust on My City Info.'),

('Franchise', 'franchise',
 'Franchise outlets of national or international brands operating in your city.',
 'Franchise Outlets Near You | My City Info',
 'franchise, branded outlet, chain store, national brand, franchise store, authorised franchise',
 'Find franchise outlets of your favourite brands in your city. Official brand stores on My City Info.'),

('Startup', 'startup',
 'Young, innovative businesses and startups bringing fresh ideas and services to the local market.',
 'Startups Near You | My City Info',
 'startup, new business, innovative, young company, fresh business, tech startup, local startup',
 'Discover innovative startups operating in your city. Support local new businesses on My City Info.'),

('Home-Based', 'home-based',
 'Businesses run from a home setup — bakers, tutors, tailors, designers, and other home-based professionals.',
 'Home-Based Businesses Near You | My City Info',
 'home based, work from home, home business, home baker, home tutor, home tailor, home studio',
 'Find home-based businesses in your city. Unique personal services from local home professionals on My City Info.'),

('Eco-Friendly', 'eco-friendly',
 'Businesses with sustainable, eco-friendly practices — green packaging, low waste, organic materials, etc.',
 'Eco-Friendly Businesses Near You | My City Info',
 'eco friendly, sustainable, green business, zero waste, organic, environment friendly, eco conscious',
 'Find eco-friendly businesses in your city. Sustainable choices for a greener lifestyle on My City Info.'),

('New Opening', 'new-opening',
 'Recently opened businesses new to the area — great for discovering the latest additions to your local scene.',
 'New Businesses Opening Near You | My City Info',
 'new opening, newly opened, just opened, new restaurant, new shop, new business, latest opening',
 'Discover newly opened businesses in your city. Be among the first to visit the latest local openings on My City Info.'),

('Locally Owned', 'locally-owned',
 'Independent businesses owned and operated by local residents — not chains or franchises.',
 'Locally Owned Businesses Near You | My City Info',
 'locally owned, local business, independent business, neighbourhood shop, community business, not a chain',
 'Find locally owned and independent businesses in your city. Support your community on My City Info.');


-- ============================================================
-- GROUP 9 — Target Audience
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('For Students', 'for-students',
 'Businesses offering special deals, services, or facilities specifically beneficial for students.',
 'Student-Friendly Businesses Near You | My City Info',
 'for students, student discount, student friendly, affordable for students, near college, student offer',
 'Find student-friendly businesses in your city. Special deals and services for students on My City Info.'),

('For Senior Citizens', 'for-senior-citizens',
 'Businesses with facilities, services, or special considerations for elderly customers.',
 'Senior Citizen Services Near You | My City Info',
 'senior citizen, elderly friendly, old age, senior discount, geriatric, senior care, for elders',
 'Find senior-citizen-friendly businesses in your city. Services and care for the elderly on My City Info.'),

('For Women', 'for-women',
 'Businesses specifically catering to women — women-only gyms, ladies salons, maternity services, etc.',
 'Women-Specific Services Near You | My City Info',
 'for women, women only, ladies, female, women services, maternity, ladies gym, women wellness',
 'Find services specifically for women in your city. Women-only and women-centric businesses on My City Info.'),

('For Kids', 'for-kids',
 'Businesses providing services and products specifically designed for children and toddlers.',
 'Services for Kids Near You | My City Info',
 'for kids, children, toddlers, kids activities, kids classes, children services, play school, kids shop',
 'Find services and businesses for kids in your city. Activities, classes, and shops for children on My City Info.'),

('For Couples', 'for-couples',
 'Romantic venues, experiences, and services designed for couples — restaurants, spas, staycations, etc.',
 'Couple-Friendly Businesses Near You | My City Info',
 'for couples, couple friendly, romantic, date spot, couple spa, couple dining, couple packages',
 'Find couple-friendly restaurants, spas, and venues in your city. Romantic experiences on My City Info.'),

('Corporate Clients', 'corporate-clients',
 'Businesses with dedicated services, packages, or pricing for corporate clients and organisations.',
 'Corporate Services Near You | My City Info',
 'corporate clients, B2B, business clients, corporate package, corporate catering, corporate tie-up',
 'Find businesses offering corporate services and packages in your city. B2B solutions on My City Info.'),

('For Tourists', 'for-tourists',
 'Businesses particularly useful for tourists and visitors — tour guides, souvenir shops, travel help, etc.',
 'Tourist Services Near You | My City Info',
 'for tourists, visitor friendly, tourist service, travel help, souvenir, tourist guide, city tour',
 'Find tourist-friendly businesses in your city. Services and attractions for visitors on My City Info.');


-- ============================================================
-- GROUP 10 — Specialisations & Features
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Emergency Services', 'emergency-services',
 'Businesses providing 24/7 emergency response — ambulance, plumbing emergencies, electrical repairs, locksmiths, etc.',
 'Emergency Services Near You | My City Info',
 'emergency service, 24/7 emergency, urgent service, emergency repair, SOS, emergency response, on call',
 'Find emergency services in your city for 24/7 urgent needs. Quick response when you need it most on My City Info.'),

('Customisation Available', 'customisation-available',
 'Businesses offering customised products or services tailored to individual customer requirements.',
 'Custom Services Near You | My City Info',
 'customisation, custom made, bespoke, personalised, made to order, tailor made, custom order',
 'Find businesses offering customisation in your city. Made-to-order and personalised services on My City Info.'),

('Bulk Orders', 'bulk-orders',
 'Businesses accepting bulk orders for products or services — ideal for businesses, events, or large families.',
 'Bulk Orders Near You | My City Info',
 'bulk order, wholesale, large quantity, bulk discount, wholesale price, volume order, large order',
 'Find businesses accepting bulk orders in your city. Wholesale pricing and large quantity orders on My City Info.'),

('Wholesale', 'wholesale',
 'Businesses selling products at wholesale or trade prices — for retailers, businesses, or bulk buyers.',
 'Wholesale Suppliers Near You | My City Info',
 'wholesale, trade price, distributor, supplier, wholesale market, B2B pricing, trade discount',
 'Find wholesale suppliers in your city for trade pricing and bulk purchasing on My City Info.'),

('Gift Wrapping', 'gift-wrapping',
 'Businesses offering gift wrapping, packaging, and presentation services for purchases.',
 'Gift Wrapping Available | My City Info',
 'gift wrapping, gift packaging, gift box, wrapped gift, gift presentation, special packaging',
 'Find businesses offering gift wrapping services in your city. Beautiful presentation for every gift on My City Info.'),

('Loyalty Programme', 'loyalty-programme',
 'Businesses with membership or loyalty card programmes rewarding repeat customers.',
 'Loyalty Programme | My City Info',
 'loyalty programme, rewards card, membership, loyalty points, repeat customer rewards, points system',
 'Find businesses with loyalty programmes in your city. Earn rewards for every visit on My City Info.'),

('Accepts Exchange', 'accepts-exchange',
 'Businesses that accept product exchange or trade-in — electronics, clothing, books, vehicles, etc.',
 'Exchange Available | My City Info',
 'exchange offer, trade in, product exchange, old for new, exchange deal, swap, trade up',
 'Find businesses accepting exchanges in your city. Trade in your old items for new ones on My City Info.'),

('Government Tie-up', 'government-tie-up',
 'Businesses with official empanelment or tie-ups with government schemes — CGHS, ESIC, Ayushman Bharat, etc.',
 'Government Empanelled Businesses | My City Info',
 'government tie-up, CGHS, ESIC, Ayushman Bharat, empanelled, government scheme, government approved',
 'Find businesses with government tie-ups and empanelment in your city on My City Info.');


-- ============================================================
-- GROUP 11 — Language & Region
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('English Speaking', 'english-speaking',
 'Businesses where staff can communicate fluently in English — helpful for expats and non-regional visitors.',
 'English Speaking Businesses | My City Info',
 'English speaking, English staff, communicates in English, English service, expat friendly',
 'Find English-speaking businesses in your city. Comfortable communication in English on My City Info.'),

('Hindi Speaking', 'hindi-speaking',
 'Businesses where staff can communicate in Hindi.',
 'Hindi Speaking Businesses | My City Info',
 'Hindi speaking, Hindi staff, Hindi service, communicates in Hindi, North Indian friendly',
 'Find Hindi-speaking businesses in your city for comfortable communication in Hindi on My City Info.'),

('Marathi Speaking', 'marathi-speaking',
 'Businesses where staff can communicate in Marathi.',
 'Marathi Speaking Businesses | My City Info',
 'Marathi speaking, Marathi staff, Marathi service, Maharashtrian, Marathi language',
 'Find Marathi-speaking businesses in your city for communication in Marathi on My City Info.'),

('Tamil Speaking', 'tamil-speaking',
 'Businesses where staff can communicate in Tamil.',
 'Tamil Speaking Businesses | My City Info',
 'Tamil speaking, Tamil staff, Tamil service, Tamil Nadu, communicates in Tamil',
 'Find Tamil-speaking businesses in your city for communication in Tamil on My City Info.'),

('Telugu Speaking', 'telugu-speaking',
 'Businesses where staff can communicate in Telugu.',
 'Telugu Speaking Businesses | My City Info',
 'Telugu speaking, Telugu staff, Telugu service, Andhra, Telangana, communicates in Telugu',
 'Find Telugu-speaking businesses in your city for communication in Telugu on My City Info.'),

('Kannada Speaking', 'kannada-speaking',
 'Businesses where staff can communicate in Kannada.',
 'Kannada Speaking Businesses | My City Info',
 'Kannada speaking, Kannada staff, Kannada service, Karnataka, communicates in Kannada',
 'Find Kannada-speaking businesses in your city for communication in Kannada on My City Info.'),

('Bengali Speaking', 'bengali-speaking',
 'Businesses where staff can communicate in Bengali.',
 'Bengali Speaking Businesses | My City Info',
 'Bengali speaking, Bengali staff, Bengali service, West Bengal, communicates in Bengali',
 'Find Bengali-speaking businesses in your city for communication in Bengali on My City Info.');


-- ============================================================
-- GROUP 12 — Technology & Digital
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Online Booking', 'online-booking',
 'Businesses that allow customers to book appointments or services online through their website or app.',
 'Online Booking Available | My City Info',
 'online booking, book online, appointment booking, digital booking, reserve online, online reservation',
 'Find businesses with online booking in your city. Reserve your spot or appointment digitally on My City Info.'),

('App Available', 'app-available',
 'Businesses with their own mobile app for ordering, booking, tracking, or managing services.',
 'App Available | My City Info',
 'mobile app, business app, order via app, app based, download app, iOS app, Android app',
 'Find businesses with dedicated mobile apps in your city. Manage orders and bookings on the go on My City Info.'),

('Digital Invoice', 'digital-invoice',
 'Businesses that provide digital or GST-compliant invoices via email or WhatsApp.',
 'Digital Invoice | My City Info',
 'digital invoice, e-invoice, GST invoice, email bill, WhatsApp invoice, paperless billing',
 'Find businesses offering digital invoices in your city. Go paperless with e-billing on My City Info.'),

('WhatsApp Ordering', 'whatsapp-ordering',
 'Businesses that accept orders, bookings, or enquiries via WhatsApp for convenience.',
 'WhatsApp Ordering | My City Info',
 'WhatsApp order, order on WhatsApp, WhatsApp booking, WhatsApp enquiry, chat to order',
 'Find businesses accepting WhatsApp orders in your city. Easy ordering via chat on My City Info.'),

('CCTV Monitored Delivery', 'cctv-monitored-delivery',
 'Logistics and delivery businesses with CCTV-monitored packing and dispatch for secure shipments.',
 'CCTV Monitored Delivery | My City Info',
 'CCTV monitored, secure packing, monitored dispatch, safe delivery, tracked shipment, secure logistics',
 'Find logistics businesses with CCTV-monitored packing and dispatch in your city on My City Info.'),

('GPS Tracking', 'gps-tracking',
 'Logistics, courier, and transport businesses offering real-time GPS tracking of deliveries.',
 'GPS Tracking Available | My City Info',
 'GPS tracking, live tracking, real time tracking, track delivery, track package, shipment tracking',
 'Find businesses with GPS tracking for deliveries in your city. Track your order in real time on My City Info.');


-- ============================================================
-- GROUP 13 — Health & Safety
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Hygiene Certified', 'hygiene-certified',
 'Food and health businesses with FSSAI registration or hygiene certifications ensuring safe, clean standards.',
 'Hygiene Certified Businesses | My City Info',
 'hygiene certified, FSSAI registered, food safety, clean kitchen, hygienic, health certified',
 'Find hygiene-certified food and health businesses in your city. Safe and clean standards on My City Info.'),

('Sanitised Premises', 'sanitised-premises',
 'Businesses maintaining regular sanitisation and cleanliness protocols for customer safety.',
 'Sanitised Premises | My City Info',
 'sanitised, clean premises, hygiene maintained, sanitisation, disinfected, clean environment',
 'Find businesses with sanitised premises in your city. Clean and safe environments on My City Info.'),

('Sterile Equipment', 'sterile-equipment',
 'Health, beauty, and medical businesses using sterilised instruments and equipment for every customer.',
 'Sterile Equipment | My City Info',
 'sterile equipment, sterilised instruments, single use, disposable, sterile tools, clean tools',
 'Find businesses using sterile and sterilised equipment in your city. Safe practices on My City Info.'),

('Trained First Aid Staff', 'trained-first-aid-staff',
 'Businesses where staff are trained in first aid and emergency response.',
 'First Aid Trained Staff | My City Info',
 'first aid trained, emergency response, CPR trained, safety trained, medical ready staff',
 'Find businesses with first-aid trained staff in your city. Safety-conscious businesses on My City Info.'),

('No Chemicals', 'no-chemicals',
 'Businesses using chemical-free, natural, or organic products — cleaning, beauty, farming, etc.',
 'Chemical-Free Businesses | My City Info',
 'no chemicals, chemical free, natural products, organic, herbal, toxin free, green products',
 'Find chemical-free businesses in your city. Natural, organic, and safe products on My City Info.');


-- ============================================================
-- GROUP 14 — Location & Accessibility
-- ============================================================

INSERT INTO mci_tags (name, slug, description, page_title, meta_keywords, meta_description) VALUES

('Near Metro / Railway Station', 'near-metro-railway',
 'Businesses conveniently located close to a metro or railway station for easy public transport access.',
 'Near Metro Station | My City Info',
 'near metro, near railway station, metro accessible, public transport, easy to reach, commuter friendly',
 'Find businesses near metro and railway stations in your city. Easy access by public transport on My City Info.'),

('Market Area', 'market-area',
 'Businesses located in or near busy local markets, bazaars, or commercial hubs.',
 'Market Area Businesses | My City Info',
 'market area, local market, bazaar, commercial hub, shopping area, market location, near market',
 'Find businesses in market areas and commercial hubs in your city. Convenient shopping zones on My City Info.'),

('Residential Area', 'residential-area',
 'Businesses located within residential colonies and neighbourhoods, easily accessible from homes.',
 'Residential Area Businesses | My City Info',
 'residential area, colony, neighbourhood, local area, near home, locality business, society',
 'Find businesses in residential areas of your city. Services close to your home on My City Info.'),

('Mall / Shopping Centre', 'mall-shopping-centre',
 'Businesses located inside malls or shopping centres with the added convenience of food courts and facilities.',
 'Mall Based Businesses | My City Info',
 'mall, shopping centre, inside mall, mall store, shopping complex, food court, mall shop',
 'Find businesses inside malls and shopping centres in your city on My City Info.'),

('Highway / Outskirts', 'highway-outskirts',
 'Businesses located on highways or city outskirts — dhabas, resorts, warehouses, showrooms with large spaces.',
 'Highway & Outskirts Businesses | My City Info',
 'highway, outskirts, main road, bypass road, highway business, large space, accessible by highway',
 'Find businesses on highways and city outskirts for large spaces and easy road access on My City Info.');


COMMIT;

-- ============================================================
-- Summary
-- 14 groups, 100 tags total
-- All tags have: name, slug, description, page_title,
--               meta_keywords, meta_description
-- Slugs are globally unique and URL-safe
-- Encoding: utf8mb4 required
--
-- Groups:
--  1. Availability & Hours        (7 tags)
--  2. Delivery & Service Mode     (7 tags)
--  3. Pricing & Value             (6 tags)
--  4. Food & Dietary Preferences  (12 tags)
--  5. Facilities & Amenities      (11 tags)
--  6. Payment & Transaction       (6 tags)
--  7. Customer & Service Quality  (10 tags)
--  8. Business Type & Setup       (8 tags)
--  9. Target Audience             (7 tags)
-- 10. Specialisations & Features  (8 tags)
-- 11. Language & Region           (7 tags)
-- 12. Technology & Digital        (6 tags)
-- 13. Health & Safety             (5 tags)
-- 14. Location & Accessibility    (5 tags)
-- ============================================================
