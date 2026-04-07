package com.example.mobile;

import android.os.Bundle;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;

import com.example.mobile.api.ApiClient;
import com.example.mobile.model.Equipe;
import com.example.mobile.model.Tournoi;
import com.example.mobile.util.Fmt;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class TournoisActivity extends BaseActivity {

    private final List<Tournoi> data = new ArrayList<>();
    private ArrayAdapter<Tournoi> adapter;
    private TextView emptyText;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_tournois);
        setTitle("Tournois disponibles");

        ListView list = findViewById(R.id.list);
        emptyText = findViewById(R.id.emptyText);
        list.setEmptyView(emptyText);

        adapter = new ArrayAdapter<Tournoi>(this, 0, data) {
            @NonNull
            @Override
            public View getView(int position, View convertView, @NonNull ViewGroup parent) {
                if (convertView == null) {
                    convertView = getLayoutInflater().inflate(R.layout.item_tournoi, parent, false);
                }
                Tournoi t = data.get(position);
                ((TextView) convertView.findViewById(R.id.title)).setText(Fmt.dateFr(t.date));
                ((TextView) convertView.findViewById(R.id.subtitle)).setText(
                    (t.equipe ? "Tournoi par équipe" : "Tournoi individuel")
                    + (t.prix_participation != null ? " · " + t.prix_participation + " €" : ""));
                ((TextView) convertView.findViewById(R.id.chip)).setText(Fmt.etat(t.etat));

                convertView.findViewById(R.id.btnSecondary).setVisibility(View.GONE);

                TextView btn = convertView.findViewById(R.id.btnAction);
                btn.setText("S'inscrire");
                btn.setEnabled("ouvert".equals(t.etat));
                btn.setOnClickListener(v -> inscrire(t));
                return convertView;
            }
        };
        list.setAdapter(adapter);
        load();
    }

    private void load() {
        ApiClient.service().getTournois().enqueue(new Callback<List<Tournoi>>() {
            @Override public void onResponse(Call<List<Tournoi>> call, Response<List<Tournoi>> r) {
                if (r.isSuccessful() && r.body() != null) {
                    data.clear();
                    data.addAll(r.body());
                    adapter.notifyDataSetChanged();
                } else {
                    Toast.makeText(TournoisActivity.this, "Erreur " + r.code(), Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<List<Tournoi>> call, Throwable t) {
                Toast.makeText(TournoisActivity.this, "Réseau: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void inscrire(Tournoi t) {
        if (t.equipe) {
            ApiClient.service().getTournoiEquipes(t.id).enqueue(new Callback<List<Equipe>>() {
                @Override public void onResponse(Call<List<Equipe>> call, Response<List<Equipe>> r) {
                    if (!r.isSuccessful() || r.body() == null) {
                        Toast.makeText(TournoisActivity.this, "Erreur chargement équipes", Toast.LENGTH_SHORT).show();
                        return;
                    }
                    List<Equipe> equipes = r.body();
                    if (equipes.isEmpty()) {
                        new AlertDialog.Builder(TournoisActivity.this)
                            .setTitle("Tournoi en équipe")
                            .setMessage("Aucune équipe n'est disponible. Contactez un administrateur.")
                            .setPositiveButton("OK", null)
                            .show();
                        return;
                    }
                    String[] noms = new String[equipes.size()];
                    for (int i = 0; i < equipes.size(); i++) noms[i] = equipes.get(i).toString();
                    new AlertDialog.Builder(TournoisActivity.this)
                        .setTitle("Choisir une équipe")
                        .setItems(noms, (d, which) -> doInscrire(t, equipes.get(which).id))
                        .setNegativeButton("Annuler", null)
                        .show();
                }
                @Override public void onFailure(Call<List<Equipe>> call, Throwable th) {
                    Toast.makeText(TournoisActivity.this, "Réseau: " + th.getMessage(), Toast.LENGTH_LONG).show();
                }
            });
        } else {
            doInscrire(t, null);
        }
    }

    private void doInscrire(Tournoi t, Integer equipeId) {
        Map<String, Object> body = new HashMap<>();
        if (equipeId != null) body.put("equipe_id", equipeId);
        ApiClient.service().inscrire(t.id, body).enqueue(new Callback<Void>() {
            @Override public void onResponse(Call<Void> call, Response<Void> r) {
                if (r.isSuccessful()) {
                    Toast.makeText(TournoisActivity.this, "Inscription enregistrée !", Toast.LENGTH_SHORT).show();
                } else if (r.code() == 409) {
                    Toast.makeText(TournoisActivity.this, "Déjà inscrit", Toast.LENGTH_SHORT).show();
                } else {
                    Toast.makeText(TournoisActivity.this, "Erreur " + r.code(), Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<Void> call, Throwable th) {
                Toast.makeText(TournoisActivity.this, "Réseau: " + th.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
}
