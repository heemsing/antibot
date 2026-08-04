-- Sample data for testing the Analytics Service

-- Insert a test client user
INSERT INTO `users` (`email`, `password_hash`, `name`, `role`) VALUES
('client@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Client', 'client')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert sample projects
INSERT INTO `projects` (`user_id`, `name`, `domain`, `tracking_code`, `yandex_metrika_id`, `is_active`, `settings`) VALUES
(1, 'Demo E-commerce Store', 'demo-shop.example.com', UNHEX(SHA2(CONCAT(RAND(), NOW()), 256)), '105559927', 1, '{"currency":"USD","timezone":"UTC"}'),
(2, 'Blog Website', 'myblog.example.com', UNHEX(SHA2(CONCAT(RAND(), NOW()), 256)), NULL, 1, '{"timezone":"Europe/Moscow"}')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert sample goals
INSERT INTO `goals` (`project_id`, `name`, `goal_type`, `target_name`, `conditions`, `is_active`) VALUES
(1, 'Add to Cart', 'click', 'add_to_cart', '{"selector":".add-to-cart-btn","event":"click"}', 1),
(1, 'Checkout Started', 'page_view', 'checkout_start', '{"url_contains":"/checkout"}', 1),
(1, 'Purchase Completed', 'page_view', 'purchase', '{"url_contains":"/thank-you"}', 1),
(1, '60 Seconds on Page', 'time_on_page', '60sec', '{"seconds":60}', 1),
(2, 'Newsletter Signup', 'form_submit', 'newsletter', '{"form_id":"newsletter-form"}', 1),
(2, 'Contact Form', 'form_submit', 'contact', '{"form_id":"contact-form"}', 1);

-- Insert sample funnel
INSERT INTO `funnels` (`project_id`, `name`, `steps`, `is_active`) VALUES
(1, 'E-commerce Purchase Funnel', '[
  {"step":1,"name":"Product Page View","type":"page_view","url_contains":"/product"},
  {"step":2,"name":"Add to Cart","type":"click","event":"add_to_cart"},
  {"step":3,"name":"Checkout Started","type":"page_view","url_contains":"/checkout"},
  {"step":4,"name":"Purchase Completed","type":"page_view","url_contains":"/thank-you"}
]', 1);

-- Note: Events and sessions should be populated by the tracking API
-- This is just schema reference for manual testing if needed

SELECT 'Sample data inserted successfully!' as status;
