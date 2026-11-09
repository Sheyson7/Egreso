// Placa 3D conceptual de MateCodeUY. Modelo digital; impresión física pendiente.
$fn = 96;
diameter = 100;
base_height = 4;
relief = 2;

color("#176B70")
cylinder(d = diameter, h = base_height);

color("#C8ED6B")
translate([0, 0, base_height])
linear_extrude(height = relief)
text("MC", size = 34, halign = "center", valign = "center", font = "Liberation Sans:style=Bold");

color("#EAB85A")
translate([0, 0, base_height])
difference() {
    cylinder(d = 88, h = 0.8);
    translate([0, 0, -0.1]) cylinder(d = 84, h = 1);
}
