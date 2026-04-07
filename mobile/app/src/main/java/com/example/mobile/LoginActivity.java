package com.example.mobile;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.mobile.api.ApiClient;
import com.example.mobile.model.LoginRequest;
import com.example.mobile.model.LoginResponse;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class LoginActivity extends AppCompatActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        ApiClient.init(this);
        if (ApiClient.isLogged()) {
            startActivity(new Intent(this, MainActivity.class));
            finish();
            return;
        }

        EditText email = findViewById(R.id.email);
        EditText password = findViewById(R.id.password);
        Button btn = findViewById(R.id.loginBtn);
        TextView error = findViewById(R.id.errorText);

        btn.setOnClickListener(v -> {
            error.setText("");
            String e = email.getText().toString().trim();
            String p = password.getText().toString();
            if (e.isEmpty() || p.isEmpty()) {
                error.setText("Email et mot de passe requis");
                return;
            }
            btn.setEnabled(false);
            ApiClient.service().login(new LoginRequest(e, p)).enqueue(new Callback<LoginResponse>() {
                @Override public void onResponse(Call<LoginResponse> call, Response<LoginResponse> r) {
                    btn.setEnabled(true);
                    if (r.isSuccessful() && r.body() != null && r.body().token != null) {
                        LoginResponse body = r.body();
                        if (body.user.roles == null || !body.user.roles.contains("ROLE_USER") || body.user.adherent_id == null) {
                            error.setText("Ce compte n'est pas un adhérent.");
                            return;
                        }
                        ApiClient.setToken(LoginActivity.this, body.token, body.user.adherent_id);
                        startActivity(new Intent(LoginActivity.this, MainActivity.class));
                        finish();
                    } else {
                        error.setText("Identifiants invalides");
                    }
                }
                @Override public void onFailure(Call<LoginResponse> call, Throwable t) {
                    btn.setEnabled(true);
                    error.setText("Erreur réseau : " + t.getMessage());
                }
            });
        });
    }
}
