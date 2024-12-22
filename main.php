import telebot
from telebot.types import InlineKeyboardMarkup, InlineKeyboardButton, InputMediaVideo
from flask import Flask, request

# Bot tokenini kiriting
bot_token = '8068383007:AAFU6IaGQSCJeoJcQkghEB_I_rd295mC1hY'
bot = telebot.TeleBot(bot_token)

# Admin usernamesi
admin_usernames = ['NarbayevUtkirbek_95']  # Admin usernamesini qo'shing

# Majburiy kanallar
channels = [
    ('Kanal 1➕', 'https://t.me/onlinesearchbook'),
    ('Kanal 2➕', 'https://t.me/tabriknoma_uzbekistann'),
]

# Nomzodlar va ularning ovozlari (video fayllar bilan)
candidates = {
    'Raximova Munira': {'video_file': 'videos/uz.mp4', 'votes': 0},
    'Qambarova Zarifa': {'video_file': 'path_to_video_file2.mp4', 'votes': 0},
    'Narbayeva Iqbol': {'video_file': 'path_to_video_file3.mp4', 'votes': 0},
    'Alibekova O`g`iloy': {'video_file': 'path_to_video_file4.mp4', 'votes': 0},
    'Maxmudova Yulduz': {'video_file': 'path_to_video_file5.mp4', 'votes': 0},
    'Akramova Sevinch': {'video_file': 'path_to_video_file6.mp4', 'votes': 0},
    'Mamarajabova Sevara': {'video_file': 'path_to_video_file7.mp4', 'votes': 0},
    'Musurmonova Aziza': {'video_file': 'path_to_video_file8.mp4', 'votes': 0},
    'Uralova Shoira': {'video_file': 'path_to_video_file9.mp4', 'votes': 0},
    'Boltayeva Gulnoza': {'video_file': 'path_to_video_file10.mp4', 'votes': 0},
}

# Foydalanuvchilar ovozlari uchun
user_votes = {}

# Flask dasturini yaratish
app = Flask(__name__)

# Webhook URL sozlamalari
WEBHOOK_URL = f'https://bot.dostlik-akm.uz/{bot_token}'

# Webhookni o'rnatish
bot.remove_webhook()
bot.set_webhook(url=WEBHOOK_URL)

# /start komandasi
@bot.message_handler(commands=['start'])
def start(message):
    chat_id = message.chat.id

    # Adminlarga cheklov yo'q
    if message.from_user.username in admin_usernames:
        bot.send_message(chat_id, "Siz adminsiz, ovoz berish uchun tayyorsiz!")
        show_vote_menu(chat_id)
        return

    markup = InlineKeyboardMarkup()

    # Obuna bo'lish kanallari inline tugmalari
    for name, url in channels:
        markup.add(InlineKeyboardButton(text=name, url=url))

    # Obuna bo'ldim tugmasi
    markup.add(InlineKeyboardButton(
        text="Obuna bo'ldim", callback_data="subscribed"))

    bot.send_message(chat_id, "Quyidagi kanallarga obuna bo'ling va \"Obuna bo'ldim✅\" tugmasini bosing:",
                     reply_markup=markup)

# Obuna holatini tekshirish
@bot.callback_query_handler(func=lambda call: call.data == "subscribed")
def check_subscription(call):
    chat_id = call.message.chat.id
    user_id = call.from_user.id

    # Adminlarga cheklov yo'q
    if call.from_user.username in admin_usernames:
        bot.send_message(chat_id, "Siz adminsiz, ovoz berish uchun tayyorsiz!")
        show_vote_menu(chat_id)
        return

    all_subscribed = True

    # Obuna holatini tekshirish
    for name, url in channels:
        channel_username = url.split('/')[-1]
        try:
            status = bot.get_chat_member(
                f"@{channel_username}", user_id).status
            if status not in ['member', 'administrator']:
                all_subscribed = False
                break
        except Exception as e:
            print(f"Error checking subscription for {channel_username}: {e}")
            all_subscribed = False
            break

    if all_subscribed:
        bot.send_message(chat_id,
                         "Siz barcha kanallarga obuna bo'lgansiz! Ovoz berish tugmasini bosish orqali ovoz bera olasiz.")
        show_vote_menu(chat_id)
    else:
        bot.send_message(
            chat_id, "Siz hali barcha kanallarga obuna bo'lmadingiz!❌")

def show_vote_menu(chat_id):
    # Ovoz berish tugmasini ko'rsatish
    markup = InlineKeyboardMarkup()
    markup.add(InlineKeyboardButton(
        text="Ovoz berish🗳", callback_data="vote_menu"))
    markup.add(InlineKeyboardButton(
        text="Yangilash", callback_data="refresh"))
    bot.send_message(
        chat_id, "Ovoz berish uchun quyidagi tugmani bosing:", reply_markup=markup)

# Ovoz berish tugmasi bosilganda
@bot.callback_query_handler(func=lambda call: call.data == "vote_menu")
def vote_menu(call):
    chat_id = call.message.chat.id
    markup = InlineKeyboardMarkup()

    sorted_candidates = sorted(candidates.items(), key=lambda x: x[1]['votes'], reverse=True)

    for i, (name, info) in enumerate(sorted_candidates, 1):
        button_text = f"{i}-o'rin: {name} - {info['votes']} ovoz"
        markup.add(InlineKeyboardButton(text=button_text,
                   callback_data=f"candidate_{name}"))

    bot.send_message(
        chat_id, "Iltimos, ovoz berishni istagan nomzodni tanlang:", reply_markup=markup)

# Nomzod menyusi
@bot.callback_query_handler(func=lambda call: call.data.startswith('candidate_'))
def candidate_menu(call):
    chat_id = call.message.chat.id
    candidate_name = call.data.split('_', 1)[1]
    candidate_info = candidates[candidate_name]

    markup = InlineKeyboardMarkup()
    markup.add(InlineKeyboardButton(text=f"🗳 Ovoz berish {candidate_name}", callback_data=f"vote_{candidate_name}"))
    markup.add(InlineKeyboardButton(text="✈️Do'stga ulashish",
               callback_data=f"share_{candidate_name}"))
    markup.add(InlineKeyboardButton(
        text="⬅️Orqaga", callback_data="vote_menu"))

    bot.send_message(chat_id, f"{candidate_name}:\nOvozlar: {candidate_info['votes']}", reply_markup=markup)
    bot.send_video(chat_id, open(candidate_info['video_file'], 'rb'))  # Video fayl yuboriladi

# Ovoz berish funksiyasi
@bot.callback_query_handler(func=lambda call: call.data.startswith('vote_'))
def cast_vote(call):
    chat_id = call.message.chat.id
    user_id = call.from_user.id
    candidate_name = call.data.split('_', 1)[1]

    # Agar foydalanuvchi allaqachon ovoz bergan bo'lsa
    if user_id in user_votes:
        bot.send_message(chat_id, "Siz faqat bitta nomzodga ovoz bera olasiz!")
        return

    # Ovoz berish jarayoni
    candidates[candidate_name]['votes'] += 1
    user_votes[user_id] = candidate_name
    bot.send_message(chat_id,
                     f"{candidate_name} uchun ovoz berildi! Yangi ovozlar: {candidates[candidate_name]['votes']}")

    vote_menu(call)

# Ulashish funksiyasi
@bot.callback_query_handler(func=lambda call: call.data.startswith('share_'))
def share_candidate(call):
    candidate_name = call.data.split('_', 1)[1]
    candidate_info = candidates[candidate_name]

    # Ulashish uchun tayyorlanadigan xabar
    markup = InlineKeyboardMarkup()
    bot_username = bot.get_me().username
    markup.add(InlineKeyboardButton(text="Ovoz berish",
               url=f"https://t.me/{bot_username}?start=1"))

    message = f"{candidate_name}:\nIltimos, ovoz berish uchun quyidagi tugmani bosing:"
    bot.send_message(call.message.chat.id, message, reply_markup=markup)

# Webhook endpoint
@app.route('/' + bot_token, methods=['POST'])
def webhook():
    json_str = request.get_data().decode('UTF-8')
    update = telebot.types.Update.de_json(json_str)
    bot.process_new_updates([update])
    return "OK", 200

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
