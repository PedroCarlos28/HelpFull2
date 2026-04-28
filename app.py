from flask import Flask, request, jsonify
from flask_cors import CORS
import os

from google import genai
from google.genai import types

app = Flask(__name__)
CORS(app)

# Nome da variável corrigido para GOOGLE_API_KEY (tudo maiúsculo e com underline)
os.environ["GOOGLE_API_KEY"] = "AIzaSyDnfbFVGnhXiho8WrJ9YamVGEhimlU_g0w"

client = genai.Client()
model = "gemini-2.5-flash"

chat_config = types.GenerateContentConfig(
    system_instruction=(
        "Você é um assistente especializado em saúde mental. "
        "Ajuda pessoas a compreender sentimentos e oferece apoio emocional. "
        "Se sair do tema, lembre o usuário do propósito."
    )
)

@app.route("/chat", methods=["POST"])
def chat():
    user_msg = request.json.get("message", "")

    try:
        response = client.models.generate_content(
            model=model,
            contents=user_msg,
            config=chat_config
        )
        reply = response.text if hasattr(response, "text") else "Erro."
    except Exception as e:
        reply = f"Erro no servidor: {str(e)}"

    return jsonify({"reply": reply})

if __name__ == "__main__":
    app.run(port=5000, debug=True)