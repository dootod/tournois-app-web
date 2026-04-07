package com.example.mobile;

import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import com.example.mobile.api.ApiClient;
import com.example.mobile.model.Adherent;
import com.example.mobile.model.MeResponse;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ProfileActivity extends BaseActivity {

    private EditText nom, prenom, dateNaissance, ceinture, poids, genre;
    private TextView errorText;
    private Button saveBtn;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_profile);
        setTitle("Mon profil");

        nom = findViewById(R.id.nom);
        prenom = findViewById(R.id.prenom);
        dateNaissance = findViewById(R.id.date_naissance);
        ceinture = findViewById(R.id.ceinture);
        poids = findViewById(R.id.poids);
        genre = findViewById(R.id.genre);
        errorText = findViewById(R.id.errorText);
        saveBtn = findViewById(R.id.save);

        saveBtn.setEnabled(false);
        saveBtn.setOnClickListener(v -> save());
        load();
    }

    private void load() {
        errorText.setText("");
        ApiClient.service().me().enqueue(new Callback<MeResponse>() {
            @Override public void onResponse(Call<MeResponse> call, Response<MeResponse> r) {
                if (!r.isSuccessful()) {
                    errorText.setText("Erreur chargement profil (" + r.code() + ")");
                    return;
                }
                if (r.body() == null || r.body().adherent == null) {
                    errorText.setText("Aucun adhérent lié à ce compte.");
                    return;
                }
                Adherent a = r.body().adherent;
                nom.setText(a.nom != null ? a.nom : "");
                prenom.setText(a.prenom != null ? a.prenom : "");
                dateNaissance.setText(a.date_naissance != null && a.date_naissance.length() >= 10
                    ? a.date_naissance.substring(0, 10) : "");
                ceinture.setText(a.ceinture != null ? a.ceinture : "");
                poids.setText(a.poids != null ? a.poids : "");
                genre.setText(a.genre != null ? a.genre : "");
                saveBtn.setEnabled(true);
            }
            @Override public void onFailure(Call<MeResponse> call, Throwable t) {
                errorText.setText("Erreur réseau : " + t.getMessage());
            }
        });
    }

    private void save() {
        errorText.setText("");

        if (nom.getText().toString().trim().isEmpty() || prenom.getText().toString().trim().isEmpty()) {
            errorText.setText("Nom et prénom obligatoires");
            return;
        }
        String date = dateNaissance.getText().toString().trim();
        if (!date.matches("\\d{4}-\\d{2}-\\d{2}")) {
            errorText.setText("Date de naissance attendue au format YYYY-MM-DD");
            return;
        }

        Adherent a = new Adherent();
        a.nom = nom.getText().toString().trim();
        a.prenom = prenom.getText().toString().trim();
        a.date_naissance = date;
        a.ceinture = ceinture.getText().toString().trim();
        String p = poids.getText().toString().trim();
        a.poids = p.isEmpty() ? null : p;
        String g = genre.getText().toString().trim();
        a.genre = g.isEmpty() ? null : g;

        saveBtn.setEnabled(false);
        ApiClient.service().updateAdherent(a).enqueue(new Callback<Adherent>() {
            @Override public void onResponse(Call<Adherent> call, Response<Adherent> r) {
                saveBtn.setEnabled(true);
                if (r.isSuccessful()) {
                    Toast.makeText(ProfileActivity.this, "Profil mis à jour", Toast.LENGTH_SHORT).show();
                    finish();
                } else {
                    errorText.setText("Erreur serveur (" + r.code() + ")");
                }
            }
            @Override public void onFailure(Call<Adherent> call, Throwable t) {
                saveBtn.setEnabled(true);
                errorText.setText("Erreur réseau : " + t.getMessage());
            }
        });
    }
}
