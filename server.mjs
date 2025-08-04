import express from 'express';
import fetch from 'node-fetch';
import dotenv from 'dotenv';

dotenv.config();

const app = express();
const PORT = 3000;

app.use(express.static('public'));

app.get('/api/boards', async (req, res) => {
  try {
    const { Trello_api_key, Trello_token } = process.env;

    const response = await fetch(`https://api.trello.com/1/members/me/boards?key=${Trello_api_key}&token=${Trello_token }`);
    const data = await response.json();

    res.json(data);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch Trello boards' });
  }
});

app.listen(PORT, () => console.log(`✅ Server running at http://localhost:${PORT}`));
