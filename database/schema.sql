CREATE TABLE reports (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  reference_no TEXT UNIQUE NOT NULL,
  fraud_type TEXT NOT NULL,
  platform TEXT NOT NULL,
  incident_date TEXT NOT NULL,
  amount REAL DEFAULT 0,
  description TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'Received',
  created_at TEXT NOT NULL
);
