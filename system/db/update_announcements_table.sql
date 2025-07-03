-- Add created_by and role columns to announcements table
ALTER TABLE announcements
ADD COLUMN created_by INT NULL,
ADD COLUMN role VARCHAR(20) NULL;

-- (Optional) Add foreign key if you want to enforce user linkage
-- ALTER TABLE announcements ADD CONSTRAINT fk_ann_created_by FOREIGN KEY (created_by) REFERENCES users(id);
