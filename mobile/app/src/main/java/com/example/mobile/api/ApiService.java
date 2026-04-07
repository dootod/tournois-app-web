package com.example.mobile.api;

import com.example.mobile.model.Adherent;
import com.example.mobile.model.Equipe;
import com.example.mobile.model.Match;
import com.example.mobile.model.LoginRequest;
import com.example.mobile.model.LoginResponse;
import com.example.mobile.model.MeResponse;
import com.example.mobile.model.Score;
import com.example.mobile.model.Tournoi;

import java.util.List;
import java.util.Map;

import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.DELETE;
import retrofit2.http.GET;
import retrofit2.http.POST;
import retrofit2.http.PUT;
import retrofit2.http.Path;

public interface ApiService {
    @POST("login")
    Call<LoginResponse> login(@Body LoginRequest body);

    @GET("me")
    Call<MeResponse> me();

    @GET("tournois")
    Call<List<Tournoi>> getTournois();

    @GET("me/tournois")
    Call<List<Tournoi>> getMesTournois();

    @POST("me/tournois/{id}/inscription")
    Call<Void> inscrire(@Path("id") int tournoiId, @Body Map<String, Object> body);

    @GET("me/tournois/{id}/equipes")
    Call<List<Equipe>> getTournoiEquipes(@Path("id") int tournoiId);

    @GET("me/tournois/{id}/matchs")
    Call<List<Match>> getMesMatchs(@Path("id") int tournoiId);

    @DELETE("me/tournois/{id}/inscription")
    Call<Void> desinscrire(@Path("id") int tournoiId);

    @PUT("me/adherent")
    Call<Adherent> updateAdherent(@Body Adherent adherent);

    @GET("me/scores")
    Call<List<Score>> getMesScores();
}
